<?php

declare(strict_types=1);

namespace App\Services;

use Google_Client;

/**
 * Verifies Google Identity Services ID tokens server-side.
 *
 * Returns the token payload (sub, email, email_verified, name, picture)
 * or throws when the token is invalid. Wrapped in a dedicated class so
 * tests can mock it without network access.
 */
class GoogleTokenVerifier
{
    /**
     * @return array{sub:string,email:string,email_verified:bool,name:string,picture?:string}
     *
     * @throws \RuntimeException When GOOGLE_CLIENT_ID is missing or the token is invalid.
     */
    public function verify(string $idToken): array
    {
        $clientId = (string) config('services.google.client_id');

        if ($clientId === '') {
            throw new \RuntimeException('Connexion Google non configurée (GOOGLE_CLIENT_ID manquant).');
        }

        $client = new Google_Client(['client_id' => $clientId]);

        $payload = $client->verifyIdToken($idToken);

        if (! is_array($payload) || ($payload['email'] ?? null) === null) {
            throw new \RuntimeException('Jeton Google invalide ou expiré.');
        }

        return [
            'sub' => (string) ($payload['sub'] ?? ''),
            'email' => strtolower((string) $payload['email']),
            'email_verified' => filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'name' => (string) ($payload['name'] ?? $payload['given_name'] ?? ''),
            'picture' => isset($payload['picture']) ? (string) $payload['picture'] : null,
        ];
    }
}
