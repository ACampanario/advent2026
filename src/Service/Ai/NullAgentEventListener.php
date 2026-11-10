<?php
declare(strict_types=1);

namespace App\Service\Ai;

/**
 * Listener that ignores every event (Part I: no streaming).
 */
class NullAgentEventListener implements AgentEventListenerInterface
{
    /**
     * @inheritDoc
     */
    public function onThinking(string $text): void
    {
    }

    /**
     * @inheritDoc
     */
    public function onText(string $text): void
    {
    }

    /**
     * @inheritDoc
     */
    public function onToolStart(string $name, array $input): void
    {
    }

    /**
     * @inheritDoc
     */
    public function onToolEnd(string $name, int $rows, bool $isError): void
    {
    }

    /**
     * @inheritDoc
     */
    public function onDone(array $summary): void
    {
    }

    /**
     * @inheritDoc
     */
    public function onError(string $message): void
    {
    }
}
