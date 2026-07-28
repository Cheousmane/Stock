<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

describe('Registration', function () {

    it('can register a user and company', function () {
        $payload = [
            'company_name' => 'Test Corp',
            'company_slug' => 'test-corp',
            'name' => 'John Doe',
            'email' => 'john@test-corp.com',
            'password' => 'Test@12345',
            'password_confirmation' => 'Test@12345',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure([
                'user' => ['uuid', 'name', 'email', 'company'],
                'access_token',
                'token_type',
            ]);

        $this->assertDatabaseHas('companies', ['slug' => 'test-corp']);
        $this->assertDatabaseHas('users', ['email' => 'john@test-corp.com']);
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['company_name', 'company_slug', 'name', 'email', 'password']);
    });

    it('validates password confirmation', function () {
        $payload = [
            'company_name' => 'Test Corp',
            'company_slug' => 'test-corp',
            'name' => 'John Doe',
            'email' => 'john@test-corp.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['password']);
    });

    it('returns token and user data on registration', function () {
        $payload = [
            'company_name' => 'Token Corp',
            'company_slug' => 'token-corp',
            'name' => 'Token User',
            'email' => 'token@test.com',
            'password' => 'Test@12345',
            'password_confirmation' => 'Test@12345',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        expect($response->json('access_token'))->not->toBeNull();
        expect($response->json('token_type'))->toBe('Bearer');
        expect($response->json('user.email'))->toBe('token@test.com');
    });

    it('rejects duplicate company slug', function () {
        Company::factory()->create(['slug' => 'duplicate-corp']);

        $payload = [
            'company_name' => 'Duplicate Corp',
            'company_slug' => 'duplicate-corp',
            'name' => 'John Doe',
            'email' => 'john@duplicate.com',
            'password' => 'Test@12345',
            'password_confirmation' => 'Test@12345',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['company_slug']);
    });

});
