<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\Draw;
use App\Models\GameType;
use App\Models\User;
use Tests\TestCase;

class BetTest extends TestCase
{
    /** @test */
    public function it_identifies_a_winning_bet_against_a_draw()
    {
        $gameType = GameType::factory()->create();

        $draw = Draw::create([
            'game_type_id' => $gameType->id,
            'draw_date' => now(),
            'numbers' => [1, 2, 3, 4, 5, 6],
        ]);

        $user = User::factory()->create();

        $bet = Bet::create([
            'user_id' => $user->id,
            'game_type_id' => $gameType->id,
            'numbers' => [1, 2, 3, 4, 5, 6],
        ]);

        $intersection = array_intersect($draw->numbers, $bet->numbers);
        $this->assertCount(6, $intersection);
    }
}
