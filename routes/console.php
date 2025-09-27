 <?php

use App\Models\Draw;
use App\Models\DrawDetail;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('add-details', function () {
    $draws = Draw::get();
    $today = Carbon::today('Asia/Kolkata')->format('Y-m-d');
    foreach ($draws as $draw) {
        DrawDetail::updateOrCreate([
            'date' => $today,
            'draw_id' => $draw->id,
        ],
            [
                'draw_id' => $draw->id,
                'start_time' => $draw->start_time,
                'end_time' => $draw->end_time,
            ]
        );
    }
    $this->info('working...');
});
Schedule::call(function () {
    $draws = Draw::get();
    $today = Carbon::today('Asia/Kolkata')->format('Y-m-d');

    foreach ($draws as $draw) {
        DrawDetail::updateOrCreate([
            'date' => $today,
            'draw_id' => $draw->id,
        ],
            [
                'draw_id' => $draw->id,
                'start_time' => $draw->start_time,
                'end_time' => $draw->end_time,
            ]
        );
    }
})->dailyAt('04:00');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('test', function () {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    DB::table('ticket_options')->truncate();
    DB::table('tickets')->truncate();
    DB::table('options')->truncate();
    DB::table('user_draws')->truncate();
    DB::table('cross_abc_details')->truncate();
    DB::table('cross_abcs')->truncate();
    DB::table('draw_details')->truncate();
    $draws = Draw::get();
    foreach ($draws as $draw) {
        DrawDetail::updateOrCreate([
            'date' => Carbon::today(),
            'draw_id' => $draw->id,
        ],
            [
                'draw_id' => $draw->id,
                'start_time' => $draw->start_time,
                'end_time' => $draw->end_time,
            ]
        );
    }

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    $this->info('done!');

});

Artisan::command('neeraj', function () {
    $draw_details = DrawDetail::find(28);
    $ab = 12;
    $ac = 14;
    $bc = 24;
    $sum_of_type = $draw_details->crossAbcDetail()
        ->select('type', DB::raw('SUM(amount) as total_amount'))
        ->where('number', $ab)
        ->groupBy('type')
        ->pluck('total_amount', 'type');

    $input['claim_ab'] = $sum_of_type['AB'] ?? 0;
    $input['claim_ac'] = $sum_of_type['AC'] ?? 0;
    $input['claim_bc'] = $sum_of_type['BC'] ?? 0;
    $draw_details->update($input);
    $this->info('updated');
});
