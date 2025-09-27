<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\Game;
use App\Traits\TicketNumber;
use Livewire\Component;

class ShopKeeperForm extends Component
{
    use TicketNumber;

    public $first_name;
    public $last_name;
    public $email;
    public $password;
    public $password_confirmation;
    public $mobile_number;
    public $showPassword = false;
    public $showConfirmPassword = false;
    public $maximum_cross_amount = 0;
    public $maximum_tq = 0;
    public $existingUser = null;

    // ✅ new property for allowed games
    public $allowed_games = [];

    public function rules()
    {
        $user = $this->existingUser;
        if ($user) {
            // Editing existing user
            return [
                'first_name' => 'required|string|min:3|max:25',
                'last_name'  => 'required|string|min:3|max:25',
                'email'      => ['nullable', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
                'mobile_number' => ['nullable', 'string', 'min:10', 'max:12', 'unique:users,mobile_number,' . $user->id],
                'password'   => ['nullable', 'string', 'min:6', 'max:20', 'confirmed'],
                'maximum_cross_amount' => ['nullable', 'numeric'],
                'maximum_tq' => ['nullable', 'numeric'],

                // ✅ validation for allowed games
                'allowed_games'   => ['required', 'array', 'min:1'],
                'allowed_games.*' => ['integer', 'exists:games,id'],
            ];
        }

        // Creating new user
        return [
            'first_name' => 'required|string|min:3|max:25',
            'last_name'  => 'required|string|min:3|max:25',
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'mobile_number' => ['required', 'string', 'min:10', 'max:12', 'unique:users,mobile_number'],
            'password'   => ['required', 'string', 'min:6', 'max:20', 'confirmed'],
            'maximum_cross_amount' => ['required', 'numeric'],
            'maximum_tq' => ['required', 'numeric'],

            // ✅ validation for allowed games
            'allowed_games'   => ['required', 'array', 'min:1'],
            'allowed_games.*' => ['integer', 'exists:games,id'],
        ];
    }

    public function getUser($user_id)
    {
        return User::where('id', $user_id)->first();
    }

    public function mount($user = null)
    {
        if ($user) {
            $this->first_name = $user->first_name;
            $this->last_name = $user->last_name;
            $this->email = $user->email;
            $this->mobile_number = $user->mobile_number;
            $this->maximum_cross_amount = $user->maximum_cross_amount;
            $this->maximum_tq = $user->maximum_tq;
            $this->existingUser = $user;

            // ✅ preload allowed games when editing
            $this->allowed_games = $user->games->pluck('id')->toArray();
        }
    }

    public function togglePasswordVisibility($field)
    {
        if ($field === 'password') {
            $this->showPassword = ! $this->showPassword;
        } elseif ($field === 'confirm') {
            $this->showConfirmPassword = ! $this->showConfirmPassword;
        }
    }

    public function save()
    {
        $shop_keeper_input_data = $this->validate();
        $shop_keeper_input_data['password_plain'] = $this->password;

        if (!empty($this->password)) {
            $shop_keeper_input_data['password_plain'] = $this->password;
            $shop_keeper_input_data['password'] = $this->password;
        } else {
            unset($shop_keeper_input_data['password']);
            unset($shop_keeper_input_data['password_plain']);
        }

        if ($this->existingUser) {
            $this->existingUser->update($shop_keeper_input_data);

            // ✅ sync allowed games on edit
            $this->existingUser->games()->sync($this->allowed_games);
        } else {
            $authUser = auth()->user();
            $shop_keeper_input_data['created_by'] = $authUser->id;
            $user = User::create($shop_keeper_input_data);
            if($authUser->hasRole('master')){
                $user->assignRole('admin');
            } else if ($authUser->hasRole('admin') ){
                $user->assignRole('shopkeeper');
            } else if ($authUser->hasRole('shopkeeper')) {
                $user->assignRole('user');
            }

            $user['ticket_series'] = $this->generateTicketNumberFromId($user->id);
            $user->save();

            // ✅ sync allowed games on create
            $user->games()->sync($this->allowed_games);
        }

        return redirect()->route('admin.shopkeepers');
    }

    public function render()
    {
        return view('livewire.admin.shop-keeper-form', [
            'games' => Game::all(), // ✅ pass games to the blade
        ]);
    }
}
