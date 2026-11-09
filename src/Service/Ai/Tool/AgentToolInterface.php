<?php
declare(strict_types=1);

namespace App\Service\Ai\Tool;

use Authorization\IdentityInterface;

/**
 * A read-only tool the agent can call. Every tool runs under the identity's authorization scope.
 */
interface AgentToolInterface
{
    /**
     * Tool name as seen by the model.
     *
     * @return string
     */
    public function name(): string;

    /**
     * Short, explicit description (part of the prompt).
     *
     * @return string
     */
    public function description(): string;

    /**
     * JSON Schema of the input.
     *
     * @return array<string, mixed>
     */
    public function inputSchema(): array;

    /**
     * Runs the tool for the given identity.
     *
     * @param array<string, mixed> $input Input sent by the model.
     * @param \Authorization\IdentityInterface $identity Logged-in user.
     * @return \App\Service\Ai\Tool\ToolResult
     */
    public function execute(array $input, IdentityInterface $identity): ToolResult;
}
