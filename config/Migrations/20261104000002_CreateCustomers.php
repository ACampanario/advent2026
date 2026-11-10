<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Creates the customers table; each customer is assigned to one sales rep.
 */
class CreateCustomers extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('customers')
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('name', 'string', ['limit' => 150, 'null' => false])
            ->addColumn('company', 'string', ['limit' => 150, 'null' => false])
            ->addColumn('email', 'string', ['limit' => 150, 'null' => false])
            ->addColumn('city', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('country', 'string', ['limit' => 2, 'null' => false])
            ->addTimestamps('created', 'modified')
            ->addIndex(['user_id'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->create();
    }
}
