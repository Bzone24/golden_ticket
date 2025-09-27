<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'mobile_number',
        'email',
        'password',
        'name',
        'password_plain',
        'ticket_series',
        'maximum_cross_amount',
        'maximum_tq',
        'created_by'
    ];

    protected $appends = ['name'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function password(): Attribute
    {
        return Attribute::make(set: fn(string $password) => Hash::make($password)); // This return hash password
    }

    protected function passwordPlain(): Attribute
    {
        return Attribute::make(
            set: fn(string $password) => Crypt::encryptString($password),
            get: fn(string $password) => Crypt::decryptString($password)
        );
    }

    protected function name(): Attribute
    {
        return Attribute::get(
            fn() => "{$this->first_name} {$this->last_name}"
        );
    }

    protected function CreatedAt(): Attribute
    {
        return Attribute::get(
            fn($created_at) => Carbon::parse($created_at)->format('Y-m-d')
        );
    }

    protected function UpdatedAt(): Attribute
    {
        return Attribute::get(
            fn($updated_at) => Carbon::parse($updated_at)->format('Y-m-d')
        );
    }

    public function scopeForName(Builder $query, string $name): Builder
    {
        return $query->where(function ($q) use ($name) {
            $q->where('first_name', 'like', "%{$name}%")
                ->orWhere('last_name', 'like', "%{$name}%");
        });
    }

    public function drawDetails()
    {
        return $this->belongsToMany(DrawDetail::class, 'user_draws');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function options()
    {
        return $this->hasMany(Options::class);
    }

    public function ticketOptions()
    {
        return $this->hasMany(TicketOption::class, 'user_id', 'id');
    }

    public function crossAbc()
    {
        return $this->hasMany(CrossAbc::class);
    }

    public function crossAbcDetail()
    {
        return $this->hasMany(CrossAbcDetail::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function children()
    {
        return $this->hasMany(User::class, 'created_by');
    }

    public function games()
{
    return $this->belongsToMany(\App\Models\Game::class, 'user_games', 'user_id', 'game_id');
}
}
