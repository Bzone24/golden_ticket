<?php

    namespace App\Models;

    // use Illuminate\Contracts\Auth\MustVerifyEmail;
    use Illuminate\Support\Str;
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
            'full_name',      // <- new
            'username',       // <- new
            'login_id',
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
       protected $casts = [
    'email_verified_at' => 'datetime',
    // leave password hashing to the mutator we have above
];

      protected function password(): Attribute
{
    return Attribute::make(
        set: function ($password) {
            // if null/empty, do not overwrite password (return null so attribute not set)
            if ($password === null || $password === '') {
                return null;
            }

            // hash when needed
            return Hash::needsRehash($password) ? Hash::make($password) : $password;
        }
    );
}


      protected function passwordPlain(): Attribute
{
    return Attribute::make(
        set: function ($password) {
            if ($password === null || $password === '') {
                return null;
            }

            // encrypt, but guard for exceptions
            try {
                return Crypt::encryptString($password);
            } catch (\Throwable $e) {
                \Log::warning('passwordPlain encrypt failed: ' . $e->getMessage());
                return null;
            }
        },
        get: function ($value) {
            if ($value === null || $value === '') {
                return null;
            }

            try {
                return Crypt::decryptString($value);
            } catch (\Throwable $e) {
                \Log::warning('passwordPlain decrypt failed: ' . $e->getMessage());
                return null;
            }
        }
    );
}


      protected function name(): Attribute
{
    return Attribute::make(
        get: fn() => $this->full_name ?? trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''))
    );
}

      protected function CreatedAt(): Attribute
{
    return Attribute::make(
        get: fn($created_at) => $created_at ? Carbon::parse($created_at)->format('Y-m-d') : null
    );
}

      protected function UpdatedAt(): Attribute
{
    return Attribute::make(
        get: fn($updated_at) => $updated_at ? Carbon::parse($updated_at)->format('Y-m-d') : null
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
    // Optional: keep backward compatibility if other code uses $user->name
        public function getNameAttribute()
        {
            return $this->full_name ?? trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
        }

        // Optional helper (call after creating the user) to set login_id if not set:
        public static function generateNextLoginId(): string
        {
            // fetch max numeric suffix, then +1. This uses DB math and is safe-ish for low concurrency.
            $max = static::whereNotNull('login_id')
                ->selectRaw('MAX(CAST(SUBSTRING(login_id, 4) AS UNSIGNED)) as maxnum')
                ->value('maxnum');

            $next = ($max ? intval($max) : 100) + 1; // start at 101 if none found
            return 'ABC' . $next;
        }
    }
