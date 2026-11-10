<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Creates the users table (sales reps and admins).
 */
class CreateUsers extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('users')
            ->addColumn('username', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('password', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('role', 'string', ['limit' => 20, 'null' => false])
            ->addTimestamps('created', 'modified')
            ->addIndex(['username'], ['unique' => true])
            ->create();
    }
}
