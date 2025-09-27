<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('web.auth.login');
    }

    public function login(LoginRequest $loginRequest)
    {
        $credentials = $loginRequest->only('email', 'password');
        if (! Auth::attempt($credentials)) {
            return back()->withErrors(['email' => 'The provided credentials do not match our records. '])->withInput();
        }
        $loginRequest->session()->regenerate();

        return redirect()->route('dashboard');

    }
}
