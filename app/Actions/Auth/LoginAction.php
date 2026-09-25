<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\LoginDTO;
use App\Models\LoginLog;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

/**
 * Class LoginAction
 * Authenticates user credentials and sets the tenant context.
 */
class LoginAction
{
    private const MAX_FAILED_ATTEMPTS = 5;
    private const LOCKOUT_WINDOW_MINUTES = 15;

    /**
     * Execute the action.
     *
     * @throws ValidationException
     */
    public function execute(LoginDTO $dto): User
    {
        $user = User::withoutGlobalScopes()
            ->with('company')
            ->where('email', $dto->email)
            ->first();

        // Check for account lockout due to too many failed attempts
        if ($user) {
            $this->checkLockout($user);
        }

        $success = $user && Hash::check($dto->password, $user->password);

        $this->logAttempt($dto->email, $user, $success);

        if (!$success) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Votre compte utilisateur a été suspendu.'],
            ]);
        }

        if ($user->email_verified_at === null) {
            throw ValidationException::withMessages([
                'email_verification' => ['Votre adresse e-mail n\'a pas été vérifiée. Un code de confirmation vous a été envoyé.'],
            ]);
        }

        if ($user->company && $user->company->status === 'suspended') {
            throw ValidationException::withMessages([
                'email' => ['Le compte de votre entreprise a été suspendu.'],
            ]);
        }

        TenantContext::set($user->company);

        $user->update(['last_login_at' => now()]);

        return $user;
    }

    private function checkLockout(User $user): void
    {
        $recentFailures = LoginLog::where('user_id', $user->id)
            ->where('success', false)
            ->where('login_at', '>=', Carbon::now()->subMinutes(self::LOCKOUT_WINDOW_MINUTES))
            ->count();

        if ($recentFailures >= self::MAX_FAILED_ATTEMPTS) {
            $lockoutUntil = Carbon::now()->subMinutes(self::LOCKOUT_WINDOW_MINUTES)->addMinutes(self::LOCKOUT_WINDOW_MINUTES);
            throw ValidationException::withMessages([
                'email' => [sprintf(
                    'Trop de tentatives de connexion échouées. Réessayez dans %d minutes.',
                    self::LOCKOUT_WINDOW_MINUTES
                )],
            ]);
        }
    }

    private function logAttempt(string $email, ?User $user, bool $success): void
    {
        LoginLog::create([
            'user_id' => $user?->id,
            'company_id' => $user?->company_id,
            'email' => $email,
            'success' => $success,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'method' => 'login',
            'login_at' => now(),
        ]);
    }
}
