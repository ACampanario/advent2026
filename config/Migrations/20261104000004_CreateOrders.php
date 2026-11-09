<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Creates the orders table.
 */
class CreateOrders extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('orders')
            ->addColumn('customer_id', 'integer', ['null' => false])
            ->addColumn('reference', 'string', ['limit' => 20, 'null' => false])
            ->addColumn('status', 'string', ['limit' => 20, 'null' => false])
            ->addColumn('ordered_at', 'timestamp', ['null' => false])
            ->addColumn('total', 'decimal', ['precision' => 12, 'scale' => 2, 'null' => false])
            ->addColumn('notes', 'text', ['null' => true, 'default' => null])
            ->addTimestamps('created', 'modified')
            ->addIndex(['reference'], ['unique' => true])
            ->addIndex(['customer_id'])
            ->addIndex(['ordered_at'])
            ->addIndex(['status'])
            ->addForeignKey('customer_id', 'customers', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->create();
    }
}
