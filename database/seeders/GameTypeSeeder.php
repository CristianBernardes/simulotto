<?php

namespace Database\Seeders;

use App\Models\GameType;
use Illuminate\Database\Seeder;

class GameTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $games = [
            [
                'name' => 'MegaZord',
                'max_numbers' => 6,
                'number_range' => 60,
                'price' => 4.50,
                'is_active' => true,
            ],
            [
                'name' => 'Aleatorix',
                'max_numbers' => 15,
                'number_range' => 25,
                'price' => 2.50,
                'is_active' => true,
            ],
            [
                'name' => 'BitPick',
                'max_numbers' => 50,
                'number_range' => 100,
                'price' => 2.00,
                'is_active' => true,
            ],
        ];

        foreach ($games as $game) {
            GameType::create($game);
        }
    }
}
