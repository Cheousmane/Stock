<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\LoginDTO;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Class LoginAction
 * Authenticates user credentials and sets the tenant context.
 */
class LoginAction
{
    /**
     * Execute the action.
     *
     * @throws ValidationException
     */
    public function execute(LoginDTO $dto): User
    {
        $user = User::withoutGlobalScopes()->where('email', $dto->email)->first();

        if (!$user || !Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        TenantContext::set($user->company);

        $user->update(['last_login_at' => now()]);

        return $user;
    }
}
