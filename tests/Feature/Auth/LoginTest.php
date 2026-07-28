<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

describe('Login', function () {

    it('can login with valid credentials and returns token', function () {
        $company = Company::factory()->create(['slug' => 'login-test']);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'admin@login-test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->withHeaders(['X-Tenant-Slug' => 'login-test'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'admin@login-test.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'user' => ['uuid', 'name', 'email', 'company'],
                'access_token',
                'token_type',
            ]);
    });

    it('returns 422 with invalid credentials', function () {
        $company = Company::factory()->create(['slug' => 'invalid-login']);
        User::factory()->create([
            'company_id' => $company->id,
            'email' => 'admin@invalid.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->withHeaders(['X-Tenant-Slug' => 'invalid-login'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'admin@invalid.com',
                'password' => 'wrong-password',
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    it('can logout and revoke token', function () {
        $company = Company::factory()->create(['slug' => 'logout-test']);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'admin@logout-test.com',
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'logout-test',
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('message', 'Déconnexion réussie.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    });

    it('unauthenticated user cannot access logout', function () {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    });

});
