<?php
declare(strict_types=1);

use Cake\I18n\DateTime;
use Faker\Factory;
use Migrations\BaseSeed;

/**
 * Demo customers: 5 assigned to `sales1` and 5 assigned to `sales2`.
 */
class CustomersSeed extends BaseSeed
{
    /**
     * Customers created per sales rep.
     *
     * @var int
     */
    private const PER_REP = 5;

    /**
     * Seeds that must run before this one.
     *
     * @return array<string>
     */
    public function getDependencies(): array
    {
        return ['UsersSeed'];
    }

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

        $repIds = $this->getAdapter()->getSelectBuilder()
            ->select(['username', 'id'])
            ->from('users')
            ->whereInList('username', ['sales1', 'sales2'])
            ->execute()
            ->fetchAll('assoc');
        $repIds = array_column($repIds, 'id', 'username');

        $rows = [];
        foreach (['sales1', 'sales2'] as $username) {
            for ($i = 0; $i < self::PER_REP; $i++) {
                $rows[] = [
                    'user_id' => (int)$repIds[$username],
                    'name' => $faker->name(),
                    'company' => $faker->company(),
                    'email' => $faker->unique()->safeEmail(),
                    'city' => $faker->city(),
                    'country' => $faker->randomElement(['US', 'CA', 'GB', 'DE', 'FR', 'ES']),
                    'created' => $now,
                    'modified' => $now,
                ];
            }
        }

        $this->table('customers')->insert($rows)->saveData();
    }
}
