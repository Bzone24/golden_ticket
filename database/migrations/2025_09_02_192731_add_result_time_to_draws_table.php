<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Draw;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('draws', function (Blueprint $table) {
            $table->string('result_time', 255)->nullable()->after('end_time');
        });

        // ✅ Update all existing draws
        $draws = Draw::all();
        foreach ($draws as $draw) {
            try {
                // Parse start_time
                $start = Carbon::createFromFormat('H:i', $draw->start_time);

                // End = start + 14 minutes
                $end = $start->copy()->addMinutes(14);

                // Result = end + 1 minute
                $result = $end->copy()->addMinute();

                // Save back
                $draw->end_time = $end->format('H:i');
                $draw->result_time = $result->format('H:i');
                $draw->save();
            } catch (\Exception $e) {
                // Skip invalid records
            }
        }
    }

    public function down(): void
    {
        Schema::table('draws', function (Blueprint $table) {
            $table->dropColumn('result_time');
        });
    }
};
