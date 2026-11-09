<?php
declare(strict_types=1);

namespace App\Service\Ai\Tool;

use Authorization\IdentityInterface;
use Cake\Log\Log;
use Throwable;

/**
 * Holds the agent tools in a fixed order (part of the cached prompt prefix),
 * exposes their API definitions and executes them safely.
 */
class ToolRegistry
{
    /**
     * @var array<string, \App\Service\Ai\Tool\AgentToolInterface>
     */
    private array $tools = [];

    /**
     * @param \App\Service\Ai\Tool\AgentToolInterface ...$tools Tools, in the order sent to the API.
     */
    public function __construct(AgentToolInterface ...$tools)
    {
        foreach ($tools as $tool) {
            $this->tools[$tool->name()] = $tool;
        }
    }

    /**
     * Tool definitions for the Messages API.
     *
     * @return list<array<string, mixed>>
     */
    public function definitions(): array
    {
        $definitions = [];
        foreach ($this->tools as $tool) {
            $definitions[] = [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'inputSchema' => $tool->inputSchema(),
                'strict' => true,
            ];
        }

        return $definitions;
    }

    /**
     * Executes a tool and measures it. Never throws: failures become error results.
     *
     * @param string $name Tool name.
     * @param array<string, mixed> $input Input sent by the model.
     * @param \Authorization\IdentityInterface $identity Logged-in user.
     * @return \App\Service\Ai\Tool\ToolResult
     */
    public function execute(string $name, array $input, IdentityInterface $identity): ToolResult
    {
        $start = hrtime(true);
        $tool = $this->tools[$name] ?? null;

        if ($tool === null) {
            $result = ToolResult::error(sprintf('Unknown tool "%s".', $name));
        } else {
            try {
                $result = $tool->execute($input, $identity);
            } catch (Throwable $e) {
                // Demo for developers: the technical detail is shown on screen (and seen by the model).
                $detail = sprintf('%s: %s', $e::class, $e->getMessage());
                Log::error(sprintf('Tool %s failed: %s', $name, $detail));
                $result = ToolResult::error('The tool failed unexpectedly (' . $detail . ').');
            }
        }

        return $result->withDuration(intdiv(hrtime(true) - $start, 1_000_000));
    }
}
