<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketEvent extends Model
{
    protected $table = 'ticket_events';
    protected $guarded = [];
    protected $casts = [
        'draw_detail_ids' => 'array',
        'details' => 'array',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
