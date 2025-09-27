<?php

namespace App\Models;

use App\Traits\AuthUser;
use App\Traits\DrawDetailsTrait;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserDraw extends Pivot
{
    use AuthUser,DrawDetailsTrait;

    protected $table = 'user_draws';

    protected $fillable = ['user_id', 'game_id', 'draw_detail_id', 'total_draws'];

    public function ticketOptions(): HasMany
    {
        return $this->hasMany(TicketOption::class, 'draw_detail_id', 'draw_detail_id')
            ->where('user_id', $this->user_id);
    }

    public function crossAbcDetail(): HasMany
    {
        return $this->hasMany(CrossAbcDetail::class, 'draw_detail_id', 'draw_detail_id')
            ->where('user_id', $this->user_id);
    }

    public function drawDetail(): BelongsTo
    {
        return $this->belongsTo(DrawDetail::class);
    }
    public function game()
{
    return $this->belongsTo(Game::class);
}


    /**
     * Get the user that owns the UserDraw
     */

    /**
     * Get the user that owns the UserDraw
     */
    // public function user(): BelongsTo
    // {
    //     return $this->belongsTo(User::class);
    // }

    // public function scopeForUser($user_id): Builder
    // {
    //     return $this->where('user_id', $user_id);
    // }

    // public function totalCompletedOptions(): int
    // {
    //     // return $this->options()->forCompletedTicket()->count();
    //     // return
    //     return $this->options()
    //         ->whereHas('ticket', function ($query) {
    //             $query->where('status', 'COMPLETED');
    //         })
    //         ->count();
    // }

    // public function totalRunningOptions(): int
    // {
    //     return $this->options()->forRunningTicket()->count();
    //     // ->whereHas('ticket', function ($query) {
    //     //     $query->where('status', 'RUNNING');
    //     // })

    // }

    // public function totalOptions(): int
    // {
    //     return $this->options()->forTicket()->count();
    // }
}
