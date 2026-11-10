<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Creates the conversations table (one chat thread per row).
 */
class CreateConversations extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('conversations')
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('title', 'string', ['limit' => 120, 'null' => false])
            ->addTimestamps('created', 'modified')
            ->addIndex(['user_id'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->create();
    }
}
