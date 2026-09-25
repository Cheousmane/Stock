<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\VerificationCodeMail;
use App\Models\EmailVerification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class EmailVerificationService
{
    public const CODE_LIFETIME_MINUTES = 20;

    public const MAX_ATTEMPTS = 5;

    public const RESEND_COOLDOWN_SECONDS = 60;

    public function sendCode(string $email, string $userName): void
    {
        $code = (string) random_int(100000, 999999);

        EmailVerification::updateOrCreate(
            ['email' => $email],
            [
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(self::CODE_LIFETIME_MINUTES),
                'attempts' => 0,
            ],
        );

        // Envoi synchrone : le code de vérification ne doit pas dépendre d'un worker de queue,
        // sinon l'utilisateur est bloqué sans mail si le worker ne tourne pas.
        Mail::to($email)->send(new VerificationCodeMail($email, $code, $userName));
    }

    public function canResend(string $email): bool
    {
        $verification = EmailVerification::where('email', $email)->latest()->first();

        return $verification === null
            || $verification->updated_at->lt(now()->subSeconds(self::RESEND_COOLDOWN_SECONDS));
    }

    /**
     * @return array{status: 'ok'|'expired'|'invalid'|'blocked', message: string}
     */
    public function verify(string $email, string $code): array
    {
        $verification = EmailVerification::where('email', $email)->latest()->first();

        if ($verification === null) {
            return ['status' => 'invalid', 'message' => 'Aucun code de vérification n\'a été envoyé à cette adresse.'];
        }

        if ($verification->expires_at->lt(now())) {
            return ['status' => 'expired', 'message' => 'Ce code a expiré. Demandez un nouveau code.'];
        }

        if ($verification->attempts >= self::MAX_ATTEMPTS) {
            return ['status' => 'blocked', 'message' => 'Trop de tentatives. Demandez un nouveau code.'];
        }

        $verification->increment('attempts');

        if (! Hash::check($code, $verification->code_hash)) {
            $remaining = self::MAX_ATTEMPTS - $verification->attempts;

            return [
                'status' => 'invalid',
                'message' => 'Code incorrect.' . ($remaining > 0 ? " Il vous reste {$remaining} tentative(s)." : ''),
            ];
        }

        $verification->delete();

        return ['status' => 'ok', 'message' => 'Adresse e-mail vérifiée.'];
    }
}
