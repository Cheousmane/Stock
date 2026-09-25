<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\GoogleTokenVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

function mockGoogleProfile(array $overrides = []): array
{
    return array_merge([
        'sub' => 'google-sub-123',
        'email' => 'marie@example.com',
        'email_verified' => true,
        'name' => 'Marie Dupont',
        'picture' => 'https://example.com/avatar.jpg',
    ], $overrides);
}

function fakeGoogleVerifier(array $profile): void
{
    test()->mock(GoogleTokenVerifier::class, function ($mock) use ($profile) {
        $mock->shouldReceive('verify')->andReturn($profile);
    });
}

describe('Google OAuth', function () {
    it('exposes whether a Google identity is new or existing', function () {
        fakeGoogleVerifier(mockGoogleProfile());

        $this->postJson('/api/v1/auth/google/profile', ['id_token' => 'fake-token'])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('is_new', true)
            ->assertJsonPath('email', 'marie@example.com');
    });

    it('rejects invalid Google tokens', function () {
        test()->mock(GoogleTokenVerifier::class, function ($mock) {
            $mock->shouldReceive('verify')->andThrow(new \RuntimeException('Jeton Google invalide ou expiré.'));
        });

        $this->postJson('/api/v1/auth/google/profile', ['id_token' => 'bad-token'])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['id_token']);
    });

    it('creates company and verified user from a new Google identity', function () {
        fakeGoogleVerifier(mockGoogleProfile());

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'fake-token',
            'company_name' => 'Dupont SARL',
            'company_slug' => 'dupont-sarl',
        ]);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure([
                'user' => ['uuid', 'name', 'email', 'company'],
                'access_token',
                'token_type',
            ]);

        $this->assertDatabaseHas('companies', ['slug' => 'dupont-sarl']);
        $this->assertDatabaseHas('users', [
            'email' => 'marie@example.com',
            'google_id' => 'google-sub-123',
        ]);

        $user = User::withoutGlobalScopes()->where('email', 'marie@example.com')->first();
        expect($user->email_verified_at)->not->toBeNull();
    });

    it('requires company fields for new Google identities', function () {
        fakeGoogleVerifier(mockGoogleProfile());

        $this->postJson('/api/v1/auth/google', ['id_token' => 'fake-token'])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['company_name', 'company_slug']);
    });

    it('logs in an existing Google user directly', function () {
        fakeGoogleVerifier(mockGoogleProfile());

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'fake-token',
            'company_name' => 'Dupont SARL',
            'company_slug' => 'dupont-sarl',
        ])->assertStatus(Response::HTTP_CREATED);

        // Second appel : connexion directe, sans entreprise.
        $this->postJson('/api/v1/auth/google', ['id_token' => 'fake-token'])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['user', 'access_token', 'token_type']);
    });

    it('auto-links a password account sharing the Google email', function () {
        $this->postJson('/api/v1/auth/register', [
            'company_name' => 'Legacy Corp',
            'company_slug' => 'legacy-corp',
            'name' => 'Marie Dupont',
            'email' => 'marie@example.com',
            'password' => 'Test@12345',
            'password_confirmation' => 'Test@12345',
        ])->assertStatus(Response::HTTP_CREATED);

        // L'e-mail est vérifié via le code classique pour isoler le test de liaison.
        $user = User::withoutGlobalScopes()->where('email', 'marie@example.com')->first();
        $user->forceFill(['email_verified_at' => now()])->save();

        fakeGoogleVerifier(mockGoogleProfile());

        $this->postJson('/api/v1/auth/google', ['id_token' => 'fake-token'])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('user.email', 'marie@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'marie@example.com',
            'google_id' => 'google-sub-123',
        ]);
    });
});
