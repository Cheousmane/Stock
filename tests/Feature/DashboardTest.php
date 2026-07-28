<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

describe('Dashboard API', function () {

    function dashboardAuthUser(string $slug = 'acme'): array
    {
        $company = Company::factory()->create(['slug' => $slug]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => "admin@{$slug}.com",
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        return [$company, $user, $token];
    }

    it('returns company stats', function () {
        [$company, $user, $token] = dashboardAuthUser();
        Customer::factory()->count(3)->create(['company_id' => $company->id]);
        Product::factory()->count(5)->create(['company_id' => $company->id]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/dashboard');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'total_revenue_xof',
                'total_invoices',
                'total_customers',
                'total_products',
                'low_stock_products',
                'recent_invoices',
                'revenue_by_month',
                'top_products',
            ]);
    });

    it('shows correct customer and product counts', function () {
        [$company, $user, $token] = dashboardAuthUser();
        Customer::factory()->count(3)->create(['company_id' => $company->id]);
        Product::factory()->count(5)->create(['company_id' => $company->id]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/dashboard');

        $response->assertStatus(Response::HTTP_OK);
        expect($response->json('total_customers'))->toBe(3);
        expect($response->json('total_products'))->toBe(5);
    });

    it('returns 401 for unauthenticated access', function () {
        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    });

});
