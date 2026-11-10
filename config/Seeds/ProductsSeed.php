<?php
declare(strict_types=1);

use Cake\I18n\DateTime;
use Faker\Factory;
use Migrations\BaseSeed;

/**
 * Demo catalogue: 30 products in 5 categories, prices between 5 and 500 USD.
 */
class ProductsSeed extends BaseSeed
{
    /**
     * Product names per category; the key prefix is used to build the SKU.
     *
     * @var array<string, array{prefix: string, items: array<string>}>
     */
    private const CATALOGUE = [
        'Office' => [
            'prefix' => 'OFF',
            'items' => [
                'Ergonomic Chair',
                'Standing Desk',
                'Desk Lamp',
                'Monitor Arm',
                'Filing Cabinet',
                'Whiteboard',
            ],
        ],
        'Electronics' => [
            'prefix' => 'ELE',
            'items' => [
                'Wireless Mouse',
                'Mechanical Keyboard',
                '27-inch Monitor',
                'USB-C Dock',
                'Noise-Cancelling Headset',
                'Webcam HD',
            ],
        ],
        'Stationery' => [
            'prefix' => 'STA',
            'items' => [
                'Notebook A5',
                'Gel Pen Pack',
                'Sticky Notes',
                'Stapler',
                'Highlighter Set',
                'Paper Ream',
            ],
        ],
        'Networking' => [
            'prefix' => 'NET',
            'items' => [
                'Wi-Fi Router',
                'Network Switch 8-Port',
                'Ethernet Cable 10m',
                'Access Point',
                'Powerline Adapter',
                'Patch Panel',
            ],
        ],
        'Storage' => [
            'prefix' => 'STO',
            'items' => [
                'External SSD 1TB',
                'USB Flash Drive 64GB',
                'NAS Enclosure',
                'Hard Drive 4TB',
                'SD Card 128GB',
                'Backup Drive 2TB',
            ],
        ],
    ];

    /**
     * Run Method.
     *
     * @return void
     */
    public function run(): void
    {
        $faker = Factory::create('en_US');
        $faker->seed(42);
        $now = DateTime::now('UTC')->format('Y-m-d H:i:s');

        $rows = [];
        foreach (self::CATALOGUE as $category => $group) {
            foreach ($group['items'] as $index => $name) {
                $rows[] = [
                    'sku' => sprintf('%s-%03d', $group['prefix'], $index + 1),
                    'name' => $name,
                    'category' => $category,
                    'price' => number_format($faker->randomFloat(2, 5, 500), 2, '.', ''),
                    'created' => $now,
                    'modified' => $now,
                ];
            }
        }

        $this->table('products')->insert($rows)->saveData();
    }
}
