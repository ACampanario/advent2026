<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Creates the order_items table (order lines).
 */
class CreateOrderItems extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('order_items')
            ->addColumn('order_id', 'integer', ['null' => false])
            ->addColumn('product_id', 'integer', ['null' => false])
            ->addColumn('quantity', 'integer', ['null' => false])
            ->addColumn('unit_price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
            ->addColumn('line_total', 'decimal', ['precision' => 12, 'scale' => 2, 'null' => false])
            ->addTimestamps('created', 'modified')
            ->addIndex(['order_id'])
            ->addIndex(['product_id'])
            ->addForeignKey('order_id', 'orders', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('product_id', 'products', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->create();
    }
}
