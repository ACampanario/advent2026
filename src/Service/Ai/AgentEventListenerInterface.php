<?php
declare(strict_types=1);

namespace App\Service\Ai;

/**
 * Receives agent progress events. No-op in Part I; Part II streams them over SSE.
 */
interface AgentEventListenerInterface
{
    /**
     * @param string $text Summarized thinking.
     * @return void
     */
    public function onThinking(string $text): void;

    /**
     * @param string $text Answer text.
     * @return void
     */
    public function onText(string $text): void;

    /**
     * @param string $name Tool name.
     * @param array<string, mixed> $input Tool input.
     * @return void
     */
    public function onToolStart(string $name, array $input): void;

    /**
     * @param string $name Tool name.
     * @param int $rows Rows returned.
     * @param bool $isError Whether the call failed.
     * @return void
     */
    public function onToolEnd(string $name, int $rows, bool $isError): void;

    /**
     * @param array<string, mixed> $summary Run summary.
     * @return void
     */
    public function onDone(array $summary): void;

    /**
     * @param string $message User-facing error message.
     * @return void
     */
    public function onError(string $message): void;
}
