<?php

namespace App\Models;

use App\Traits\AuthUser;
use Illuminate\Database\Eloquent\Model;

class CrossAbc extends Model
{
    use AuthUser;

    protected $fillable = ['game_id', 'user_id',  'ticket_id', 'abc',
        'combination', 'qty', 'ab', 'ac', 'bc', 'number', 'option', 'draw_details_ids', 'amt'];

    protected $casts = [
        'ab' => 'array',
        'ac' => 'array',
        'bc' => 'array',
        'draw_details_ids' => 'array',
    ];
}
