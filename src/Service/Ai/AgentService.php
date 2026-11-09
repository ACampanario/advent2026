<?php
declare(strict_types=1);

namespace App\Service\Ai;

use Anthropic\Core\Exceptions\AnthropicException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Messages\Message as ApiMessage;
use App\Model\Entity\Conversation;
use App\Model\Table\MessagesTable;
use App\Service\Ai\Tool\ToolRegistry;
use Authorization\IdentityInterface;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use stdClass;

/**
 * Manual agent loop: model call → tool calls → model call … until the model answers.
 */
class AgentService
{
    /**
     * Shown when the iteration cap is reached. Never sent back to the API.
     *
     * @var string
     */
    public const NOTICE_ITERATION_CAP = "I couldn't complete this request within the allowed number of steps. "
        . 'Please try a more specific question.';

    /**
     * Shown when the answer was cut by `max_tokens`.
     *
     * @var string
     */
    public const NOTICE_MAX_TOKENS = 'The answer was cut because it reached the maximum length.';

    /**
     * Shown when the model refuses (no server-side fallback on Claude Haiku 5.5).
     *
     * @var string
     */
    public const NOTICE_REFUSAL = "I can't help with that request.";

    /**
     * Prefix of the notice shown on API errors (followed by the technical detail).
     *
     * @var string
     */
    public const NOTICE_ERROR = 'Anthropic API error';

    /**
     * Prefix of the notice shown on stop reasons the loop does not handle.
     *
     * @var string
     */
    public const NOTICE_UNEXPECTED_STOP = 'Unexpected stop reason';

    /**
     * @param \App\Service\Ai\ClaudeGateway $gateway Anthropic API wrapper.
     * @param \App\Service\Ai\Tool\ToolRegistry $tools Agent tools.
     * @param \App\Service\Ai\SystemPrompt $systemPrompt System prompt builder.
     * @param \App\Service\Ai\CostCalculator $costCalculator Cost per call.
     * @param \App\Model\Table\MessagesTable $messages Conversation history storage.
     * @param string $model Model id.
     * @param string $effort Output effort (`low`, `medium`, `high`).
     * @param int $maxTokens Max tokens per model call.
     * @param int $maxIterations Max model calls per user question.
     */
    public function __construct(
        private ClaudeGateway $gateway,
        private ToolRegistry $tools,
        private SystemPrompt $systemPrompt,
        private CostCalculator $costCalculator,
        private MessagesTable $messages,
        private string $model,
        private string $effort,
        private int $maxTokens,
        private int $maxIterations,
    ) {
    }

    /**
     * Answers one user question in a conversation.
     *
     * @param \App\Model\Entity\Conversation $conversation Conversation (already authorized).
     * @param string $userText User question.
     * @param \Authorization\IdentityInterface $identity Logged-in user; every tool runs under its scope.
     * @param \App\Service\Ai\AgentEventListenerInterface $listener Progress events.
     * @return \App\Service\Ai\AgentRunResult
     */
    public function run(
        Conversation $conversation,
        string $userText,
        IdentityInterface $identity,
        AgentEventListenerInterface $listener,
    ): AgentRunResult {
        // The user turn is stored first, so the question stays visible even if the API fails.
        $userTurn = ['role' => 'user', 'content' => [['type' => 'text', 'text' => $userText]]];
        /** @see \App\Model\Table\MessagesTable::appendTurns() */
        $this->messages->appendTurns($conversation->id, [$userTurn]);

        $history = $this->history($conversation->id);
        /** @see \App\Service\Ai\SystemPrompt::blocks() */
        $system = $this->systemPrompt->blocks((string)$identity['role'], DateTime::now('UTC'));
        /** @see \App\Service\Ai\Tool\ToolRegistry::definitions() */
        $toolDefinitions = $this->tools->definitions();

        $iterations = 0;
        $stopReason = null;
        $text = '';
        $notice = null;

        try {
            while (true) {
                if ($iterations >= $this->maxIterations) {
                    $notice = self::NOTICE_ITERATION_CAP;
                    break;
                }
                $iterations++;

                /** @see \App\Service\Ai\ClaudeGateway::createMessage() */
                $response = $this->gateway->createMessage([
                    'model' => $this->model,
                    'maxTokens' => $this->maxTokens,
                    'system' => $system,
                    'tools' => $toolDefinitions,
                    'messages' => $history,
                    'thinking' => ['type' => 'adaptive', 'display' => 'summarized'],
                    'outputConfig' => ['effort' => $this->effort],
                ]);
                $stopReason = $response->stopReason;
                $blocks = $this->toWire($response->content);
                $this->emitContent($blocks, $listener);
                $assistantTurn = ['role' => 'assistant', 'content' => $blocks] + $this->usageData($response);

                if ($stopReason !== 'tool_use') {
                    /** @see \App\Model\Table\MessagesTable::appendTurns() */
                    $this->messages->appendTurns($conversation->id, [$assistantTurn]);
                    $text = $this->text($blocks);
                    $notice = match ($stopReason) {
                        'end_turn' => null,
                        'max_tokens' => self::NOTICE_MAX_TOKENS,
                        'refusal' => self::NOTICE_REFUSAL,
                        default => sprintf('%s: %s', self::NOTICE_UNEXPECTED_STOP, $stopReason ?? 'null'),
                    };
                    break;
                }

                // Run every tool_use block; all results go back in ONE user message.
                [$results, $toolCalls] = $this->runTools($blocks, $identity, $listener);
                $assistantTurn['tool_calls'] = $toolCalls;
                $resultTurn = ['role' => 'user', 'content' => $results];

                // tool_use and tool_result are stored together, so the history is always valid.
                /** @see \App\Model\Table\MessagesTable::appendTurns() */
                $this->messages->appendTurns($conversation->id, [$assistantTurn, $resultTurn]);
                $history[] = $this->forApi($assistantTurn);
                $history[] = $resultTurn;
            }
        } catch (AnthropicException $e) {
            // Demo for developers: show the technical detail (exception class, HTTP status, message).
            $status = $e instanceof APIStatusException && $e->status !== null ? ' (' . $e->status . ')' : '';
            $notice = sprintf('%s: %s%s: %s', self::NOTICE_ERROR, $e::class, $status, $e->getMessage());
            Log::error(sprintf('Conversation %d: %s', $conversation->id, $notice));
            $listener->onError($notice);
        }

        $result = new AgentRunResult($text, $stopReason, $notice, $iterations);
        $listener->onDone(['stopReason' => $stopReason, 'iterations' => $iterations, 'notice' => $notice]);

        return $result;
    }

    /**
     * Stored history in the shape the API expects.
     *
     * @param int $conversationId Conversation id.
     * @return list<array<string, mixed>>
     */
    private function history(int $conversationId): array
    {
        $history = [];
        /** @see \App\Model\Table\MessagesTable::findHistory() */
        foreach ($this->messages->find('history', conversationId: $conversationId)->all() as $message) {
            $history[] = $this->forApi(['role' => $message->role, 'content' => $message->content]);
        }

        return $history;
    }

    /**
     * Keeps only role/content and restores empty tool inputs as JSON objects.
     *
     * JSON storage decodes `{}` as `[]`, but the API requires `input` to be an object.
     *
     * @param array<string, mixed> $turn Stored or in-memory turn.
     * @return array<string, mixed>
     */
    private function forApi(array $turn): array
    {
        $content = $turn['content'];
        foreach ($content as $i => $block) {
            if (($block['type'] ?? null) === 'tool_use' && $block['input'] === []) {
                $content[$i]['input'] = new stdClass();
            }
        }

        return ['role' => $turn['role'], 'content' => $content];
    }

    /**
     * Converts SDK content blocks to wire arrays (thinking signatures included, unchanged).
     *
     * @param array<mixed> $content SDK content blocks.
     * @return list<array<string, mixed>>
     */
    private function toWire(array $content): array
    {
        /** @var list<array<string, mixed>> $blocks */
        $blocks = json_decode((string)json_encode(array_values($content)), true);

        return $blocks;
    }

    /**
     * Executes the tool_use blocks of one response.
     *
     * @param list<array<string, mixed>> $blocks Assistant content blocks.
     * @param \Authorization\IdentityInterface $identity Logged-in user.
     * @param \App\Service\Ai\AgentEventListenerInterface $listener Progress events.
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>} tool_result blocks and tool_calls rows.
     */
    private function runTools(array $blocks, IdentityInterface $identity, AgentEventListenerInterface $listener): array
    {
        $results = [];
        $toolCalls = [];
        foreach ($blocks as $block) {
            if ($block['type'] !== 'tool_use') {
                continue;
            }
            $input = is_array($block['input']) ? $block['input'] : [];
            $listener->onToolStart($block['name'], $input);
            /** @see \App\Service\Ai\Tool\ToolRegistry::execute() */
            $result = $this->tools->execute($block['name'], $input, $identity);
            $listener->onToolEnd($block['name'], $result->rowCount, $result->isError);

            $toolResult = [
                'type' => 'tool_result',
                'tool_use_id' => $block['id'],
                'content' => (string)json_encode($result->data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ];
            if ($result->isError) {
                $toolResult['is_error'] = true;
            }
            $results[] = $toolResult;

            $toolCalls[] = [
                'tool_use_id' => $block['id'],
                'name' => $block['name'],
                'input' => $input,
                'row_count' => $result->rowCount,
                'truncated' => $result->truncated,
                'is_error' => $result->isError,
                'duration_ms' => $result->durationMs,
            ];
        }

        return [$results, $toolCalls];
    }

    /**
     * Usage, model, stop reason and cost of one response, as message columns.
     *
     * @param \Anthropic\Messages\Message $response API response.
     * @return array<string, mixed>
     */
    private function usageData(ApiMessage $response): array
    {
        $usage = $response->usage;
        $cacheCreation = $usage->cacheCreationInputTokens ?? 0;
        $cacheRead = $usage->cacheReadInputTokens ?? 0;

        return [
            'stop_reason' => $response->stopReason,
            'model' => $response->model,
            'input_tokens' => $usage->inputTokens,
            'output_tokens' => $usage->outputTokens,
            'cache_creation_input_tokens' => $cacheCreation,
            'cache_read_input_tokens' => $cacheRead,
            /** @see \App\Service\Ai\CostCalculator::cost() */
            'cost_usd' => $this->costCalculator->cost(
                $usage->inputTokens,
                $usage->outputTokens,
                $cacheCreation,
                $cacheRead,
            ),
        ];
    }

    /**
     * Sends thinking and text blocks to the listener.
     *
     * @param list<array<string, mixed>> $blocks Assistant content blocks.
     * @param \App\Service\Ai\AgentEventListenerInterface $listener Progress events.
     * @return void
     */
    private function emitContent(array $blocks, AgentEventListenerInterface $listener): void
    {
        foreach ($blocks as $block) {
            if ($block['type'] === 'thinking' && ($block['thinking'] ?? '') !== '') {
                $listener->onThinking($block['thinking']);
            } elseif ($block['type'] === 'text') {
                $listener->onText($block['text']);
            }
        }
    }

    /**
     * Joins the text blocks of a response.
     *
     * @param list<array<string, mixed>> $blocks Assistant content blocks.
     * @return string
     */
    private function text(array $blocks): string
    {
        $parts = [];
        foreach ($blocks as $block) {
            if ($block['type'] === 'text') {
                $parts[] = $block['text'];
            }
        }

        return implode("\n\n", $parts);
    }
}
