<?php
declare(strict_types=1);

namespace App\Service\Ai;

/**
 * Outcome of one agent run (one user question).
 */
final readonly class AgentRunResult
{
    /**
     * @param string $text Final answer text (empty if none).
     * @param string|null $stopReason Last stop reason returned by the API.
     * @param string|null $notice User-facing notice (iteration cap, max tokens, refusal, API error); never sent to the API.
     * @param int $iterations Model calls made.
     */
    public function __construct(
        public string $text,
        public ?string $stopReason,
        public ?string $notice,
        public int $iterations,
    ) {
    }
}
