<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

describe('Subscription API', function () {

    function subAuthUser(string $slug = 'acme'): array
    {
        $company = Company::factory()->create(['slug' => $slug]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => "admin@{$slug}.com",
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        return [$company, $user, $token];
    }

    it('returns plans list', function () {
        [$company, $user, $token] = subAuthUser();
        Plan::factory()->create(['is_active' => true, 'sort' => 1]);
        Plan::factory()->create(['is_active' => true, 'sort' => 2]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/subscriptions/plans');

        $response->assertStatus(Response::HTTP_OK);
    });

    it('returns current subscription (nullable)', function () {
        [$company, $user, $token] = subAuthUser();

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/subscriptions/current');

        $response->assertStatus(Response::HTTP_OK);
    });

    it('returns 401 for unauthenticated access to plans', function () {
        $response = $this->getJson('/api/v1/subscriptions/plans');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    });

});
