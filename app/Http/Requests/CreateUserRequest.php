<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'email' => 'email',
            'password' => 'mot de passe',
            'role' => 'rôle',
        ];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)->mixedCase()->letters()->numbers()->symbols(), 'confirmed'],
            'role' => ['sometimes', 'string', 'exists:roles,name', 'nullable'],
        ];
    }
}
