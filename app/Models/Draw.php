<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Draw extends Model
{
    const PRICE = 11;

    // ✅ Added result_time to fillable
    protected $fillable = [
        'game_id',
        'price',
        'start_time',
        'end_time',
        'result_time',
        'status',
        'total_collection',
        'total_rewards'
    ];

    protected $appends = ['draw_number'];

    protected function price(): Attribute
    {
        return Attribute::set(
            fn() => self::PRICE
        );
    }

    protected function drawNumber(): Attribute
    {
        return Attribute::get(
            fn() => 'DN - ' . $this->id
        );
    }

    public function scopeRunningDraw(Builder $draw)
    {
        $currentTime = Carbon::now()->setTimezone('Asia/Kolkata')->format('H:i');

        return $draw->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function details()
    {
        return $this->hasMany(DrawDetail::class);
    }


    public function formatStartTime($format = 'h:i a')
    {
        return Carbon::createFromFormat('H:i', $this->start_time)->format($format);
    }

    public function formatEndTime($format = 'h:i a')
    {
        return Carbon::createFromFormat('H:i', $this->end_time)->format($format);
    }

    // ✅ New: format result_time
    public function formatResultTime($format = 'h:i a')
    {
        return Carbon::createFromFormat('H:i', $this->result_time)->format($format);
    }

    public function options()
    {
        return $this->hasMany(Options::class);
    }

    public function ticketOptions()
    {
        return $this->hasMany(TicketOption::class, 'user_id', 'id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_draws');
    }

    public function scopeForUser(Builder $draw, $auth_id): Builder
    {
        return $draw->whereHas('users', function ($q) use ($auth_id) {
            return $q->where('user_id', $auth_id);
        });
    }

    public function sumOfOptions($draw_id)
    {
        return $this->ticketOptions->sum('a_qty') + $this->ticketOptions->sum('b_qty') + $this->ticketOptions->sum('c_qty');
    }

    public function totalCollection($draw_id)
    {
        return $this->sumOfOptions($draw_id) * self::PRICE;
    }

    public function totalDistributions($draw_id)
    {
        return $this->sumOfOptions($draw_id) * 100;
    }
    // protected static function booted()
    // {
    //     static::saved(function ($draw) {
    //         $draw->details()->update([
    //             'end_time' => $draw->end_time,
    //         ]);
    //     });
    // }
}
