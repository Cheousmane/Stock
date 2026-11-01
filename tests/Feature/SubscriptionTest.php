<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Product;
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

    function superAdminAuthUser(string $slug = 'super-admin'): array
    {
        $company = Company::factory()->create(['slug' => $slug]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => "admin@{$slug}.com",
            'is_super_admin' => true,
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        test()->withHeaders([
            'X-Tenant-Slug' => $company->slug,
            'Authorization' => 'Bearer ' . $token,
        ]);

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

    it('returns plans list for unauthenticated access (public endpoint)', function () {
        Plan::factory()->create(['is_active' => true, 'sort' => 1]);
        Plan::factory()->create(['is_active' => true, 'sort' => 2]);

        $response = $this->getJson('/api/v1/subscriptions/plans');

        $response->assertStatus(Response::HTTP_OK);
    });

    it('lists companies over the free quota for the super admin', function () {
        $free = Plan::factory()->create([
            'slug' => 'free',
            'price_xof' => 0,
            'quotas' => [
                'max_users' => 5,
                'max_products' => 10,
                'max_invoices' => 5,
                'max_warehouses' => 1,
            ],
        ]);

        $overQuotaCompany = Company::factory()->create([
            'slug' => 'over-quota-co',
            'plan_id' => $free->id,
            'status' => 'active',
        ]);
        Product::factory()->count(11)->create(['company_id' => $overQuotaCompany->id]);

        [$adminCompany, $adminUser, $adminToken] = superAdminAuthUser();

        $response = $this->withHeaders([
            'X-Tenant-Slug' => $adminCompany->slug,
            'Authorization' => 'Bearer ' . $adminToken,
        ])->getJson('/api/v1/admin/subscriptions/over-quota');

        $response->assertStatus(Response::HTTP_OK);

        $rows = $response->json('data') ?? [];
        expect($rows)->not->toBeEmpty();

        $found = collect($rows)->contains(fn (array $row) => ($row['company']['slug'] ?? null) === 'over-quota-co');
        expect($found)->toBeTrue();

        $target = collect($rows)->firstWhere(fn (array $row) => ($row['company']['slug'] ?? null) === 'over-quota-co');
        expect($target['quotas']['over_quota'])->toBeTrue();
    });

});
