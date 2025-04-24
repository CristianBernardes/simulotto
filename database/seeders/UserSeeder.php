<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@simulotto.com',
                'is_admin' => true,
            ],
            [
                'name' => 'Regular User',
                'email' => 'user@simulotto.com',
                'is_admin' => false,
            ],
            [
                'name' => 'Regular User 2',
                'email' => 'user_2@simulotto.com',
                'is_admin' => false,
            ],
        ];

        foreach ($users as $user) {
            User::factory()->create($user);
        }
    }
}
