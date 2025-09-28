<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.auth.login.login');
    }

    public function login(LoginRequest $request)
{
    $login = $request->input('login');
    $password = $request->input('password');

    $user = User::where('username', $login)->orWhere('login_id', $login)->first();

    if (! $user || ! Hash::check($password, $user->password)) {
        return back()->withErrors(['login' => 'Invalid credentials'])->withInput();
    }

    Auth::login($user, $request->filled('remember'));
    $request->session()->regenerate();

    // keep your role redirect
    if ($user->hasRole('admin') || $user->hasRole('master')) {
        return redirect()->route('admin.dashboard');
    } elseif ($user->hasRole('shopkeeper')) {
        return redirect()->route('admin.dashboard');
    } else {
        return redirect()->route('user.dashboard');
    }
}
}
