<?php
declare(strict_types=1);

namespace App\Service\Ai;

/**
 * Cost of one model call in USD, from the token usage and the configured prices.
 */
class CostCalculator
{
    /**
     * @param float $input USD per million input tokens.
     * @param float $output USD per million output tokens.
     * @param float $cacheRead USD per million cache-read tokens.
     * @param float $cacheWrite USD per million cache-write tokens (5-minute cache).
     */
    public function __construct(
        private float $input,
        private float $output,
        private float $cacheRead,
        private float $cacheWrite,
    ) {
    }

    /**
     * Calculates the cost of one call.
     *
     * @param int $inputTokens Uncached input tokens.
     * @param int $outputTokens Output tokens (thinking included).
     * @param int $cacheCreationTokens Tokens written to the cache.
     * @param int $cacheReadTokens Tokens read from the cache.
     * @return float Cost in USD, rounded to 6 decimals.
     */
    public function cost(int $inputTokens, int $outputTokens, int $cacheCreationTokens, int $cacheReadTokens): float
    {
        $usd = $inputTokens * $this->input
            + $outputTokens * $this->output
            + $cacheCreationTokens * $this->cacheWrite
            + $cacheReadTokens * $this->cacheRead;

        return round($usd / 1_000_000, 6);
    }
}
