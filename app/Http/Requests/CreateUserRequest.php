<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

   public function rules(): array
{
    $rules = [
        'username' => ['required','string','alpha_dash','max:100','unique:users,username'],
        'full_name' => ['required','string','max:255'],
        'email' => ['nullable','email','max:255','unique:users,email'],
        'mobile_number' => ['nullable','string','max:50'],
        'password' => [
            'required',
            'string',
            'min:6',
            'confirmed',
        ],
    ];

    // Only enforce allowed_games if user is shopkeeper
    if (auth()->check() && auth()->user()->hasRole('shopkeeper')) {
        $rules['allowed_games']   = ['required', 'array', 'min:1'];
        $rules['allowed_games.*'] = ['integer', 'exists:games,id'];
    }

    return $rules;
}


    public function attributes()
    {
        return [
            'username' => 'Username',
            'full_name' => 'Full name',
            'mobile_number' => 'Mobile number',
            'email' => 'Email',
            'password' => 'Password',
        ];
    }
}
