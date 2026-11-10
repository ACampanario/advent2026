<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Creates the messages table; content stores the exact API content blocks.
 */
class CreateMessages extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('messages')
            ->addColumn('conversation_id', 'integer', ['null' => false])
            ->addColumn('role', 'string', ['limit' => 10, 'null' => false])
            ->addColumn('content', 'jsonb', ['null' => false])
            ->addColumn('stop_reason', 'string', ['limit' => 30, 'null' => true, 'default' => null])
            ->addColumn('model', 'string', ['limit' => 60, 'null' => true, 'default' => null])
            ->addColumn('input_tokens', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('output_tokens', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('cache_creation_input_tokens', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('cache_read_input_tokens', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('cost_usd', 'decimal', ['precision' => 12, 'scale' => 6, 'null' => false, 'default' => 0])
            ->addTimestamps('created', false)
            ->addIndex(['conversation_id'])
            ->addForeignKey('conversation_id', 'conversations', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
