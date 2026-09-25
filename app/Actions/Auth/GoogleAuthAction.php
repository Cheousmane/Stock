<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\LoginLog;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\ValidationException;

/**
 * Authenticates a user from a verified Google identity.
 *
 * - Finds by google_id, else by e-mail (auto-link when Google certifies
 *   the address as verified).
 * - Applies the same guards as password login (active, company status).
 * - Google-verified e-mails skip the 6-digit verification flow.
 */
class GoogleAuthAction
{
    /**
     * @param  array{sub:string,email:string,email_verified:bool,name:string,picture?:string}  $profile
     *
     * @throws ValidationException
     */
    public function execute(array $profile): User
    {
        if (! $profile['email_verified']) {
            throw ValidationException::withMessages([
                'email' => ['Votre adresse Google n\'est pas vérifiée. Vérifiez-la puis réessayez.'],
            ]);
        }

        $user = User::withoutGlobalScopes()
            ->with('company')
            ->where('google_id', $profile['sub'])
            ->first();

        // Liaison automatique : même adresse e-mail déjà inscrite avec mot de passe.
        if ($user === null) {
            $user = User::withoutGlobalScopes()
                ->with('company')
                ->where('email', $profile['email'])
                ->first();

            if ($user !== null) {
                $user->google_id = $profile['sub'];
                if ($user->email_verified_at === null) {
                    $user->email_verified_at = now();
                }
                if ($user->avatar === null && ($profile['picture'] ?? null) !== null) {
                    $user->avatar = $profile['picture'];
                }
                $user->save();
            }
        }

        if ($user === null) {
            throw ValidationException::withMessages([
                'account' => ['Aucun compte associé. Complétez la création de votre entreprise.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Votre compte utilisateur a été suspendu.'],
            ]);
        }

        if ($user->company && $user->company->status === 'suspended') {
            throw ValidationException::withMessages([
                'email' => ['Le compte de votre entreprise a été suspendu.'],
            ]);
        }

        TenantContext::set($user->company);

        $user->update(['last_login_at' => now()]);

        LoginLog::create([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'email' => $user->email,
            'success' => true,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'method' => 'google',
            'login_at' => now(),
        ]);

        return $user;
    }
}
