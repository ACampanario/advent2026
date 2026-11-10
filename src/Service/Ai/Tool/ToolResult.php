<?php
declare(strict_types=1);

namespace App\Service\Ai\Tool;

/**
 * Result of one tool call, sent back to the model as JSON and stored in `tool_calls`.
 */
final readonly class ToolResult
{
    /**
     * @param array<string, mixed> $data Payload returned to the model.
     * @param int $rowCount Number of rows returned (0 for errors and "not found").
     * @param bool $truncated Whether more rows exist than the ones returned.
     * @param bool $isError Whether the call failed (invalid input, unexpected error).
     * @param int $durationMs Execution time, set by the registry.
     */
    public function __construct(
        public array $data,
        public int $rowCount = 0,
        public bool $truncated = false,
        public bool $isError = false,
        public int $durationMs = 0,
    ) {
    }

    /**
     * Successful result.
     *
     * @param array<string, mixed> $data Payload.
     * @param int $rowCount Number of rows returned.
     * @param bool $truncated Whether more rows exist.
     * @return self
     */
    public static function ok(array $data, int $rowCount, bool $truncated = false): self
    {
        return new self($data, $rowCount, $truncated);
    }

    /**
     * Record not found or outside the user's scope (both look the same on purpose).
     *
     * @return self
     */
    public static function notFound(): self
    {
        return new self(['found' => false]);
    }

    /**
     * Failed call, with a short message the model can act on.
     *
     * @param string $message Error message.
     * @return self
     */
    public static function error(string $message): self
    {
        return new self(['error' => $message], 0, false, true);
    }

    /**
     * Copy of this result with the measured execution time.
     *
     * @param int $durationMs Execution time in milliseconds.
     * @return self
     */
    public function withDuration(int $durationMs): self
    {
        return new self($this->data, $this->rowCount, $this->truncated, $this->isError, $durationMs);
    }
}
