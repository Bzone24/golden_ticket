<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class DrawDetail extends Model
{
    const PRICE = 11;

    protected $fillable = [
        'game_id',
        'draw_id',
        'start_time',
        'end_time',
        'claim',
        'total_qty',
        'date',
        'claim_a',
        'claim_b',
        'claim_c',
        'ab',
        'ac',
        'bc',
        'claim_ab',
        'claim_ac',
        'claim_bc',
        'total_cross_amt'
    ];

    public function scopeRunningDraw(Builder $drawDetail)
    {
        $current_time = Carbon::now()->timezone('Asia/Kolkata')->format('H:i');
        $today = Carbon::today('Asia/Kolkata');

        return DrawDetail::whereDate('date', $today)
            ->where(function ($q) use ($current_time) {
                $q->where(function ($inner) use ($current_time) {
                    $inner->where('start_time', '<=', $current_time)
                        ->where('end_time', '>=', $current_time);
                })
                    ->orWhere('start_time', '>', $current_time);
            });
    }

    public function formatEndTime($format = 'h:i a')
    {
        return Carbon::createFromFormat('H:i', $this->end_time)->format($format);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function games()
    {
        return $this->belongsToMany(
            \App\Models\Game::class,
            'draw_detail_game',
            'draw_detail_id',
            'game_id'
        )->withTimestamps();
    }

    public function draw()
    {
        return $this->belongsTo(Draw::class);
    }


    public function formatStartTime($format = 'h:i a')
    {
        return Carbon::createFromFormat('H:i', $this->start_time)->format($format);
    }

    // ✅ New helper: Display end_time + 1 minute (result time)
    public function formatResultTime($format = 'h:i a')
    {
        return Carbon::createFromFormat('H:i', $this->end_time)
            ->addMinute()
            ->format($format);
    }

    public function ticketOptions()
    {
        return $this->hasMany(TicketOption::class);
    }

    public function crossAbcDetail()
    {
        return $this->hasMany(CrossAbcDetail::class);
    }

    public function scopeForUserTicketOption(EloquentBuilder $drawDetail, $user_id): Builder
    {
        return $drawDetail->whereHas('ticketOptions', function ($ticketOption) use ($user_id) {
            return $ticketOption->where('user_id', $user_id);
        })->orWhereHas('crossAbcDetail', function ($crossAbc) use ($user_id)  {
            return $crossAbc->where('user_id', $user_id);
        });
    }

    public function totalAqty(?int $number = null, $user_id = null)
    {
        return $this->ticketOptions()
            ->when(! is_null($number), fn($q) => $q->where('number', $number)) // ✅ works with 0
            ->when(! is_null($user_id), fn($q) => $q->where('user_id', $user_id))
            ->sum('a_qty');
    }

    public function totalBqty(?int $number = null, $user_id = null)
    {
        return $this->ticketOptions()
            ->when(! is_null($number), fn($q) => $q->where('number', $number))
            ->when($user_id, fn($q) => $q->where('user_id', $user_id))
            ->sum('b_qty');
    }

    public function totalCqty(?int $number = null, $user_id = null)
    {
        return $this->ticketOptions()
            ->when(! is_null($number), fn($q) => $q->where('number', $number))
            ->when($user_id, fn($q) => $q->where('user_id', $user_id))
            ->sum('c_qty');
    }

    public function totalAbAmt($user_id = null)
    {
        return $this->crossAbcDetail()
            ->where('type', 'AB')
            ->when($user_id, fn($q) => $q->where('user_id', $user_id))
            ->sum('amount');
    }

    public function totalAcAmt($user_id = null)
    {
        return $this->crossAbcDetail()
            ->where('type', 'AC')
            ->when($user_id, fn($q) => $q->where('user_id', $user_id))
            ->sum('amount');
    }

    public function totalBcAmt($user_id = null)
    {
        return $this->crossAbcDetail()
            ->where('type', 'BC')
            ->when($user_id, fn($q) => $q->where('user_id', $user_id))
            ->sum('amount');
    }

    // ✅ Timer helper
    public function remainingSeconds(): int
    {
        if (! $this->end_time) {
            return 0;
        }

        $end = Carbon::createFromFormat('H:i', $this->end_time, 'Asia/Kolkata');
        $now = Carbon::now('Asia/Kolkata');

        return max(0, $end->diffInSeconds($now, false));
    }
}
