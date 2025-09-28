<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('web.auth.login');
    }

    public function login(LoginRequest $loginRequest)
    {
        $login = $loginRequest->input('login');
        $password = $loginRequest->input('password');

        // Prefer matching login_id if the user entered the ABC### pattern,
        // otherwise try username first. This avoids accidental collisions
        // when a username might look like ABC123.
        if (preg_match('/^ABC\d+$/i', $login)) {
            $user = User::where('login_id', $login)->first();
        } else {
            $user = User::where('username', $login)->first();
        }

        // Fallback: if not found above, try the other column (covers edge cases)
        if (! $user) {
            $user = User::where('username', $login)->orWhere('login_id', $login)->first();
        }

        if (! $user || ! Hash::check($password, $user->password)) {
            return back()
                ->withErrors(['login' => 'The provided credentials do not match our records.'])
                ->withInput();
        }

        Auth::login($user, $loginRequest->filled('remember'));
        $loginRequest->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
