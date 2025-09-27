<?php

namespace Database\Seeders;

use App\Models\Draw;
use App\Models\Game;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DrawSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all games (example: N1, N2, N3…)
        $games = Game::all();

        foreach ($games as $game) {
            // Start at 08:30
            $start = Carbon::createFromTime(8, 30);
            // Last draw result should be before 22:00
            $lastResult = Carbon::createFromTime(22, 0);

            while ($start < $lastResult) {
                $end        = $start->copy()->addMinutes(14); // ✅ closes at 14 minutes
                $resultTime = $end->copy()->addMinute();      // ✅ result after close

                Draw::updateOrCreate(
                    [
                        'start_time' => $start->format('H:i'),
                        'game_id'    => $game->id,  // ✅ unique per game
                    ],
                    [
                        'price'       => 11,
                        'end_time'    => $end->format('H:i'),
                        'result_time' => $resultTime->format('H:i'),
                    ]
                );

                // next draw starts at result_time
                $start = $resultTime->copy();
            }
        }
    }
}
