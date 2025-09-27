<?php

namespace App\Models;

use App\Traits\AuthUser;
use App\Traits\DrawDetailsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class CrossAbcDetail extends Model
{
    use AuthUser, DrawDetailsTrait;

    protected $fillable = [
       'game_id', 'user_id', 'ticket_id', 'draw_detail_id', 'option', 'number', 'type', 'combination', 'amount'
    ];

    public function drawDetail(): BelongsTo
    {
        return $this->belongsTo(DrawDetail::class);
    }

    public function remainingMinutes(): int
    {
        if (!$this->drawDetail || !$this->drawDetail->end_time) {
            return 0;
        }

        $end = Carbon::createFromFormat('H:i', $this->drawDetail->end_time);
        $now = Carbon::now();
        return max(0, $now->diffInMinutes($end, false));
    }
}
