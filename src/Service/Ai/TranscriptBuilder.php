<?php
declare(strict_types=1);

namespace App\Service\Ai;

use App\Model\Entity\Message;

/**
 * Groups the stored messages of a conversation into "exchanges" for the chat view:
 * one exchange per user question, with the final answer, the tools used, the per-call
 * technical details and the summed usage.
 */
class TranscriptBuilder
{
    /**
     * Shown when an exchange has no final answer (iteration cap or API error).
     *
     * @var string
     */
    public const NOTICE_NO_ANSWER = 'No answer was produced.';

    /**
     * @param \App\Service\Ai\MarkdownRenderer $markdown Safe Markdown renderer.
     */
    public function __construct(private MarkdownRenderer $markdown)
    {
    }

    /**
     * Builds the transcript.
     *
     * @param iterable<\App\Model\Entity\Message> $messages Messages with tool calls, oldest first.
     * @return array{exchanges: list<array<string, mixed>>, totals: array<string, int|float>}
     */
    public function build(iterable $messages): array
    {
        $exchanges = [];
        $current = null;

        foreach ($messages as $message) {
            if ($message->role === 'user') {
                $question = $this->blocksOfType($message->content, 'text');
                if ($question !== []) {
                    if ($current !== null) {
                        $exchanges[] = $this->finish($current);
                    }
                    $current = $this->newExchange(implode("\n\n", array_column($question, 'text')));
                    continue;
                }
                if ($current !== null) {
                    $current = $this->attachToolResults($current, $message->content);
                }
                continue;
            }

            if ($current === null) {
                continue;
            }
            $current = $this->addAssistantTurn($current, $message);
        }

        if ($current !== null) {
            $exchanges[] = $this->finish($current);
        }

        return ['exchanges' => $exchanges, 'totals' => $this->totals($exchanges)];
    }

    /**
     * Empty exchange for a new user question.
     *
     * @param string $question User text.
     * @return array<string, mixed>
     */
    private function newExchange(string $question): array
    {
        return [
            'question' => $question,
            'answerHtml' => null,
            'tools' => [],
            'calls' => [],
            'usage' => ['input' => 0, 'output' => 0, 'cacheWrite' => 0, 'cacheRead' => 0, 'cost' => 0.0],
            'lastStopReason' => null,
            'notice' => null,
        ];
    }

    /**
     * Adds one assistant turn (one model call) to the exchange.
     *
     * @param array<string, mixed> $exchange Exchange.
     * @param \App\Model\Entity\Message $message Assistant message.
     * @return array<string, mixed>
     */
    private function addAssistantTurn(array $exchange, Message $message): array
    {
        $exchange['calls'][] = [
            'iteration' => count($exchange['calls']) + 1,
            'stopReason' => $message->stop_reason,
            'model' => $message->model,
            'input' => $message->input_tokens,
            'output' => $message->output_tokens,
            'cacheWrite' => $message->cache_creation_input_tokens,
            'cacheRead' => $message->cache_read_input_tokens,
            'cost' => (float)$message->cost_usd,
        ];
        $exchange['usage']['input'] += $message->input_tokens;
        $exchange['usage']['output'] += $message->output_tokens;
        $exchange['usage']['cacheWrite'] += $message->cache_creation_input_tokens;
        $exchange['usage']['cacheRead'] += $message->cache_read_input_tokens;
        $exchange['usage']['cost'] += (float)$message->cost_usd;
        $exchange['lastStopReason'] = $message->stop_reason;

        foreach ($message->tool_calls ?? [] as $toolCall) {
            $exchange['tools'][$toolCall->tool_use_id] = [
                'name' => $toolCall->name,
                'input' => $toolCall->input,
                'rows' => $toolCall->row_count,
                'truncated' => $toolCall->truncated,
                'isError' => $toolCall->is_error,
                'durationMs' => $toolCall->duration_ms,
                'result' => null,
            ];
        }

        if ($message->stop_reason !== 'tool_use') {
            $text = implode("\n\n", array_column($this->blocksOfType($message->content, 'text'), 'text'));
            if ($text !== '') {
                $exchange['answerHtml'] = $this->markdown->render($text);
            }
        }

        return $exchange;
    }

    /**
     * Attaches the tool_result payloads (as the model received them) to their tool calls.
     *
     * @param array<string, mixed> $exchange Exchange.
     * @param array<array<string, mixed>> $content User turn content.
     * @return array<string, mixed>
     */
    private function attachToolResults(array $exchange, array $content): array
    {
        foreach ($this->blocksOfType($content, 'tool_result') as $block) {
            $id = $block['tool_use_id'] ?? null;
            if ($id !== null && isset($exchange['tools'][$id])) {
                $result = $block['content'] ?? '';
                $decoded = is_string($result) ? json_decode($result, true) : $result;
                $exchange['tools'][$id]['result'] = (string)json_encode(
                    $decoded ?? $result,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                );
            }
        }

        return $exchange;
    }

    /**
     * Derives the notice of a finished exchange from what was stored.
     *
     * @param array<string, mixed> $exchange Exchange.
     * @return array<string, mixed>
     */
    private function finish(array $exchange): array
    {
        $exchange['tools'] = array_values($exchange['tools']);
        $stop = $exchange['lastStopReason'];

        $exchange['notice'] = match (true) {
            $stop === 'max_tokens' => AgentService::NOTICE_MAX_TOKENS,
            $stop === 'refusal' => AgentService::NOTICE_REFUSAL,
            $exchange['answerHtml'] === null => sprintf(
                '%s Last stop reason: %s.',
                self::NOTICE_NO_ANSWER,
                $stop ?? 'none (no model response stored)',
            ),
            !in_array($stop, ['end_turn', 'tool_use'], true) => AgentService::NOTICE_UNEXPECTED_STOP . ': ' . $stop,
            default => null,
        };

        return $exchange;
    }

    /**
     * Conversation totals.
     *
     * @param list<array<string, mixed>> $exchanges Exchanges.
     * @return array<string, int|float>
     */
    private function totals(array $exchanges): array
    {
        $totals = ['input' => 0, 'output' => 0, 'cacheWrite' => 0, 'cacheRead' => 0, 'cost' => 0.0, 'calls' => 0];
        foreach ($exchanges as $exchange) {
            foreach (['input', 'output', 'cacheWrite', 'cacheRead', 'cost'] as $key) {
                $totals[$key] += $exchange['usage'][$key];
            }
            $totals['calls'] += count($exchange['calls']);
        }

        return $totals;
    }

    /**
     * Content blocks of one type.
     *
     * @param array<array<string, mixed>> $content Message content.
     * @param string $type Block type.
     * @return list<array<string, mixed>>
     */
    private function blocksOfType(array $content, string $type): array
    {
        return array_values(array_filter($content, fn(array $block): bool => ($block['type'] ?? null) === $type));
    }
}
