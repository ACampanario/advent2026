<?php
declare(strict_types=1);

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\I18n\DateTime;
use Migrations\BaseSeed;

/**
 * Demo users: one admin and two sales reps. Password is `password` for all.
 */
class UsersSeed extends BaseSeed
{
    /**
     * Run Method.
     *
     * @return void
     */
    public function run(): void
    {
        $hasher = new DefaultPasswordHasher();
        $now = DateTime::now('UTC')->format('Y-m-d H:i:s');

        $users = [
            ['username' => 'admin', 'name' => 'Ada Admin', 'role' => 'admin'],
            ['username' => 'sales1', 'name' => 'Sam Seller', 'role' => 'sales'],
            ['username' => 'sales2', 'name' => 'Sara Seller', 'role' => 'sales'],
        ];

        $rows = [];
        foreach ($users as $user) {
            $rows[] = $user + [
                'password' => $hasher->hash('password'),
                'created' => $now,
                'modified' => $now,
            ];
        }

        $this->table('users')->insert($rows)->saveData();
    }
}
