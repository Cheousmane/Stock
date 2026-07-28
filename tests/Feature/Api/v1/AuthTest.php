<?php

declare(strict_types=1);

namespace Tests\Feature\Api\v1;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

describe('Auth', function () {

    it('can register a company, user, and return a token', function () {
        $payload = [
            'company_name' => 'Acme Corp',
            'company_slug' => 'acme-corp',
            'name' => 'John Doe',
            'email' => 'john@acme.com',
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

        $this->assertDatabaseHas('companies', ['slug' => 'acme-corp']);
        $this->assertDatabaseHas('users', ['email' => 'john@acme.com']);
    });

    it('can login with valid credentials', function () {
        $company = Company::create(['name' => 'Acme', 'slug' => 'acme']);
        User::factory()->create([
            'company_id' => $company->id,
            'email' => 'john@acme.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->withHeaders(['X-Tenant-Slug' => 'acme'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'john@acme.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'user' => ['uuid', 'name', 'email', 'company'],
                'access_token',
                'token_type',
            ]);
    });

    it('returns 422 when logging in with wrong password', function () {
        $company = Company::create(['name' => 'Acme', 'slug' => 'acme']);
        User::factory()->create([
            'company_id' => $company->id,
            'email' => 'john@acme.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->withHeaders(['X-Tenant-Slug' => 'acme'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'john@acme.com',
                'password' => 'wrong-password',
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    it('authenticated user can access /api/v1/auth/me', function () {
        $company = Company::create(['name' => 'Acme', 'slug' => 'acme']);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'john@acme.com',
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/auth/me');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('user.email', 'john@acme.com');
    });

    it('can logout and revoke token', function () {
        $company = Company::create(['name' => 'Acme', 'slug' => 'acme']);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'john@acme.com',
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('message', 'Déconnexion réussie.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    });

});
