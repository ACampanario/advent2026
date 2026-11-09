<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Creates the tool_calls table (audit log of every tool executed by the agent).
 */
class CreateToolCalls extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('tool_calls')
            ->addColumn('message_id', 'integer', ['null' => false])
            ->addColumn('tool_use_id', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 60, 'null' => false])
            ->addColumn('input', 'jsonb', ['null' => false])
            ->addColumn('row_count', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('truncated', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('is_error', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('duration_ms', 'integer', ['null' => false, 'default' => 0])
            ->addTimestamps('created', false)
            ->addIndex(['message_id'])
            ->addForeignKey('message_id', 'messages', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
