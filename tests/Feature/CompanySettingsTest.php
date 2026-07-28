<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

describe('Company Settings API', function () {

    function settingsAuthUser(string $slug = 'acme'): array
    {
        $company = Company::factory()->create(['slug' => $slug]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => "admin@{$slug}.com",
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        return [$company, $user, $token];
    }

    it('authenticated user can access auth/me', function () {
        [$company, $user, $token] = settingsAuthUser();

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/auth/me');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['user' => ['uuid', 'name', 'email', 'company']]);
    });

    it('returns 401 for unauthenticated access', function () {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    });

    it('authenticated user can see their company info', function () {
        [$company, $user, $token] = settingsAuthUser();

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/auth/me');

        $response->assertStatus(Response::HTTP_OK);
        expect($response->json('user.company.name'))->toBe($company->name);
        expect($response->json('user.company.slug'))->toBe($company->slug);
    });

});
