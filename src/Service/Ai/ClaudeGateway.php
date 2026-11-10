<?php
declare(strict_types=1);

namespace App\Service\Ai;

use Anthropic\Client;
use Anthropic\Messages\Message;

/**
 * Thin wrapper around the official Anthropic SDK client.
 */
class ClaudeGateway
{
    /**
     * @param \Anthropic\Client $client SDK client (built in the DI container with the API key).
     */
    public function __construct(private Client $client)
    {
    }

    /**
     * Calls the Messages API with SDK named parameters (camelCase).
     *
     * @param array<string, mixed> $params Named parameters for `messages->create()`.
     * @return \Anthropic\Messages\Message
     */
    public function createMessage(array $params): Message
    {
        return $this->client->messages->create(...$params);
    }
}
