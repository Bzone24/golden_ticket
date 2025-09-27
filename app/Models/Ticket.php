<?php

namespace App\Models;

use App\Traits\AuthUser;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use AuthUser;

    protected $fillable = ['game_id', 'ticket_number', 'user_id', 'draw_details_id','status'];

    protected $appends = ['full_ticket_no'];

    protected function fullTicketNo(): Attribute
    {
        return Attribute::get(
            fn () => "{$this->ticket_number}"
        );
    }

    public function scopeForTicketNumber(Builder $query, string $ticket_no): Builder
    {
        return $query->where(function ($q) use ($ticket_no) {
            $q->where('ticket_number', 'like', "%{$ticket_no}%");
        });
    }

    /**
     * Get the user that owns the Ticket
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function scopeRunning(Builder $ticket): Builder
    {
        return $ticket->where('status', 'RUNNING');
    }

    public function scopeCompleted(Builder $ticket): Builder
    {
        return $ticket->where('status', 'COMPLETED');
    }
    public function options()
{
    return $this->hasMany(\App\Models\TicketOption::class, 'ticket_id');
}

public function crossAbc()
{
    return $this->hasMany(\App\Models\CrossAbcDetail::class, 'ticket_id');
}

public function game()
{
    return $this->belongsTo(Game::class);
}

public function user()
{
    return $this->belongsTo(User::class);
}

public function drawDetail()
{
    return $this->belongsTo(DrawDetail::class);
}


}
