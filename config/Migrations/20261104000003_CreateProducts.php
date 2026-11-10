<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Creates the products catalogue table.
 */
class CreateProducts extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('products')
            ->addColumn('sku', 'string', ['limit' => 30, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 150, 'null' => false])
            ->addColumn('category', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
            ->addTimestamps('created', 'modified')
            ->addIndex(['sku'], ['unique' => true])
            ->create();
    }
}
