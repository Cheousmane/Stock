<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $password = Password::min(8)->mixedCase()->letters()->numbers()->symbols();

        if (!app()->environment('testing')) {
            $password = $password->uncompromised();
        }

        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', $password, 'confirmed'],
        ];
    }
}
