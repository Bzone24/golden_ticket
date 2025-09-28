<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\Game;
use App\Traits\TicketNumber;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ShopKeeperForm extends Component
{
    use TicketNumber;

    public $full_name;
    public $username;
    public $email;
    public $password;
    public $password_confirmation;
    public $mobile_number;
    public $showPassword = false;
    public $showConfirmPassword = false;
    public $maximum_cross_amount = 0;
    public $maximum_tq = 0;
    public $existingUser = null;
    public $per_game_limits = [];

    // allowed games (array of game ids)
    public $allowed_games = [];

    /**
     * Validation rules for create / update
     */
    public function rules()
    {
        $user = $this->existingUser;

        // Build per-game validation rules dynamically based on $this->games (Game collection)
        $gameRules = [];
        foreach (Game::all() as $g) {
            // we validate the per_game_limits keys by game id
            $gameId = $g->id;
            $gameRules["per_game_limits.{$gameId}.maximum_cross_amount"] = ['nullable', 'numeric', 'min:0'];
            $gameRules["per_game_limits.{$gameId}.maximum_tq"] = ['nullable', 'numeric', 'min:0'];
        }

        if ($user) {
            // Editing existing user - email/mobile optional, password optional
            return array_merge([
                'username' => ['required', 'string', 'alpha_dash', 'max:100', 'unique:users,username,' . $user->id],
                'full_name' => ['required', 'string', 'min:3', 'max:255'],
                'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
                'mobile_number' => ['nullable', 'string', 'min:6', 'max:20', 'unique:users,mobile_number,' . $user->id],
                'password' => ['nullable', 'string', 'min:6', 'max:100', 'confirmed'],
                'maximum_cross_amount' => ['nullable', 'numeric'],
                'maximum_tq' => ['nullable', 'numeric'],

                'allowed_games'   => ['required', 'array', 'min:1'],
                'allowed_games.*' => ['integer', 'exists:games,id'],
            ], $gameRules);
        }

        // Creating new user - require per-game limits instead of the single global? we keep both but enforce per_game_limits
        return array_merge([
            'username' => ['required', 'string', 'alpha_dash', 'max:100', 'unique:users,username'],
            'full_name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'mobile_number' => ['nullable', 'string', 'min:6', 'max:20', 'unique:users,mobile_number'],
            'password' => ['required', 'string', 'min:6', 'max:100', 'confirmed'],
            'maximum_cross_amount' => ['required', 'numeric'],
            'maximum_tq' => ['required', 'numeric'],

            'allowed_games'   => ['required', 'array', 'min:1'],
            'allowed_games.*' => ['integer', 'exists:games,id'],
        ], $gameRules);
    }


    /**
     * Helper to get user by id
     */
    public function getUser($user_id)
    {
        return User::where('id', $user_id)->first();
    }

    /**
     * Mount component - preload when editing
     *
     * $user can be a User model or null
     */
    public function mount($user = null)
    {
        if ($user) {
            // existing fields
            $this->full_name = $user->full_name ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            $this->username = $user->username;
            $this->email = $user->email;
            $this->mobile_number = $user->mobile_number;
            $this->maximum_cross_amount = $user->maximum_cross_amount;
            $this->maximum_tq = $user->maximum_tq;
            $this->existingUser = $user;

            // preload allowed games when editing (if relation exists)
            $this->allowed_games = $user->games()->pluck('games.id')->toArray();

            // Initialize per_game_limits for all games
            $games = Game::all();
            // load existing per-game rows if table exists
            if (\Schema::hasTable('user_game_limits')) {
                $rows = DB::table('user_game_limits')->where('user_id', $user->id)->get()->keyBy('game_id');
            } else {
                $rows = collect();
            }

            foreach ($games as $g) {
                $row = $rows->get($g->id);
                $this->per_game_limits[$g->id] = [
                    'maximum_cross_amount' => $row ? (int)$row->maximum_cross_amount : (int)($user->maximum_cross_amount ?? 0),
                    'maximum_tq' => $row ? (int)$row->maximum_tq : (int)($user->maximum_tq ?? 0),
                ];
            }
        }
    }


    public function togglePasswordVisibility($field = null)
    {
        // If called with no parameter, just toggle both as a safe fallback
        if ($field === null) {
            $this->showPassword = ! $this->showPassword;
            $this->showConfirmPassword = ! $this->showConfirmPassword;
            return;
        }

        if ($field === 'password') {
            $this->showPassword = ! $this->showPassword;
        } elseif ($field === 'confirm') {
            $this->showConfirmPassword = ! $this->showConfirmPassword;
        }
    }

    /**
     * Generate next login_id safely in PHP using a DB query to find max numeric suffix.
     * Starts from ABC101.
     */
    protected function generateNextLoginId(): string
    {
        // get max numeric part from existing login_id values (SUBSTRING(login_id, 4))
        $max = DB::table('users')
            ->whereNotNull('login_id')
            ->selectRaw('MAX(CAST(SUBSTRING(login_id, 4) AS UNSIGNED)) as maxnum')
            ->value('maxnum');

        $nextNum = ($max ? intval($max) : 100) + 1; // start at 101 if none
        return 'ABC' . $nextNum;
    }

    /**
     * Save or update shopkeeper
     */
    public function save()
    {
        $validated = $this->validate();

        // Prepare data for DB
        $data = [
            'username' => $validated['username'],
            'full_name' => $validated['full_name'],
            'email' => $validated['email'] ?? null,
            'mobile_number' => $validated['mobile_number'] ?? null,
            'maximum_cross_amount' => $validated['maximum_cross_amount'] ?? 0,
            'maximum_tq' => $validated['maximum_tq'] ?? 0,
        ];

        // Handle password: if provided -> hash, else do not include (so update won't clear it)
        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        DB::transaction(function () use ($data) {
            if ($this->existingUser) {
                // Update existing user
                $this->existingUser->update($data);

                // Sync allowed games
                $this->existingUser->games()->sync($this->allowed_games);

                $userId = $this->existingUser->id;
            } else {
                // Create new user (your existing code)
                $authUser = auth()->user();
                $data['created_by'] = $authUser ? $authUser->id : null;

                $user = User::create($data);

                // assign role logic...
                if ($authUser) {
                    if ($authUser->hasRole('master')) {
                        $user->assignRole('admin');
                    } elseif ($authUser->hasRole('admin')) {
                        $user->assignRole('shopkeeper');
                    } elseif ($authUser->hasRole('shopkeeper')) {
                        $user->assignRole('user');
                    }
                }

                $user->ticket_series = $this->generateTicketNumberFromId($user->id);

                if (empty($user->login_id)) {
                    $user->login_id = $this->generateNextLoginId();
                }

                $user->save();

                // Sync allowed games
                $user->games()->sync($this->allowed_games);

                $userId = $user->id;
            }

            // Persist per-game limits into user_game_limits table if it exists
            if (\Schema::hasTable('user_game_limits')) {
                foreach ($this->per_game_limits as $gameId => $limits) {
                    DB::table('user_game_limits')->updateOrInsert(
                        ['user_id' => $userId, 'game_id' => (int)$gameId],
                        [
                            'maximum_cross_amount' => (int)($limits['maximum_cross_amount'] ?? 0),
                            'maximum_tq' => (int)($limits['maximum_tq'] ?? 0),
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            } else {
                // fallback: update the user global columns (optional)
                DB::table('users')->where('id', $userId)->update([
                    'maximum_cross_amount' => $this->maximum_cross_amount,
                    'maximum_tq' => $this->maximum_tq,
                ]);
            }
        });


        return redirect()->route('admin.shopkeepers');
    }

    public function render()
    {
        return view('livewire.admin.shop-keeper-form', [
            'games' => Game::all(),
        ]);
    }
}
