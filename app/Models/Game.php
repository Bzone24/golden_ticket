<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = ['name', 'slug'];

    // Relationships
    public function draws()
    {
        return $this->hasMany(Draw::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    // public function drawDetails()
    // {
    //     return $this->hasMany(DrawDetail::class);
    // }

    public function drawDetails()
{
    return $this->belongsToMany(
        \App\Models\DrawDetail::class,
        'draw_detail_game',
        'game_id',
        'draw_detail_id'
    )->withTimestamps();
}

}
