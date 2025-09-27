<?php

namespace App\Models;

use App\Traits\AuthUser;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Options extends Model
{
    protected $fillable = ['game_id', 'user_id', 'draw_details_ids', 'ticket_id', 'number', 'option', 'qty', 'total', 'status'];

    use AuthUser;

    protected $casts = [
        'draw_details_ids' => 'array',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function scopeForTicket(Builder $options, $ticket_id): Builder
    {
        return $options->whereHas('ticket', function ($ticket) use ($ticket_id) {
            return $ticket->where('id', $ticket_id);
        });
    }

    public function scopeForRunningTicket(Builder $options): Builder
    {
        return $options->whereHas('ticket', fn ($ticket) => $ticket->running());
    }

    public function scopeForCompletedTicket(Builder $options): Builder
    {
        return $options->whereHas('ticket', fn ($ticket) => $ticket->completed());
    }

    public function scopeforCompleted(Builder $options): Builder
    {
        return $options->where('status', 'COMPLETED');
    }
    public function game()
{
    return $this->belongsTo(Game::class);
}



}
