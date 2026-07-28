<?php

declare(strict_types=1);

namespace Tests\Feature\Api\v1;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

describe('Tenant Isolation', function () {

    it('Company A cannot access Company B product', function () {
        $companyA = Company::create(['name' => 'Company A', 'slug' => 'company-a']);
        $userA = User::factory()->create([
            'company_id' => $companyA->id,
            'email' => 'admin@a.com',
        ]);
        $tokenA = $userA->createToken('auth_token')->plainTextToken;

        $companyB = Company::create(['name' => 'Company B', 'slug' => 'company-b']);
        $userB = User::factory()->create([
            'company_id' => $companyB->id,
            'email' => 'admin@b.com',
        ]);
        $tokenB = $userB->createToken('auth_token')->plainTextToken;

        $productA = Product::create([
            'company_id' => $companyA->id,
            'name' => 'Product of A',
            'sku' => 'A-SKU',
            'price' => 5000,
        ]);

        Product::create([
            'company_id' => $companyB->id,
            'name' => 'Product of B',
            'sku' => 'B-SKU',
            'price' => 8000,
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'company-a',
            'Authorization' => 'Bearer ' . $tokenA,
        ])->getJson('/api/v1/products/' . $productA->id);

        $response->assertStatus(Response::HTTP_OK);

        $indexResponse = $this->withHeaders([
            'X-Tenant-Slug' => 'company-a',
            'Authorization' => 'Bearer ' . $tokenA,
        ])->getJson('/api/v1/products');

        $indexResponse->assertStatus(Response::HTTP_OK);
        $ids = collect($indexResponse->json())->pluck('id');
        expect($ids)->toContain($productA->id);
    });

    it('Company B cannot access Company A product and gets 404', function () {
        $companyA = Company::create(['name' => 'Company A', 'slug' => 'company-a']);
        $companyB = Company::create(['name' => 'Company B', 'slug' => 'company-b']);
        $userB = User::factory()->create([
            'company_id' => $companyB->id,
            'email' => 'admin@b.com',
        ]);
        $tokenB = $userB->createToken('auth_token')->plainTextToken;

        $productA = Product::create([
            'company_id' => $companyA->id,
            'name' => 'Product of A',
            'sku' => 'A-SKU-2',
            'price' => 3000,
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'company-b',
            'Authorization' => 'Bearer ' . $tokenB,
        ])->getJson('/api/v1/products/' . $productA->id);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    });

    it('cross-tenant update returns 404', function () {
        $companyA = Company::create(['name' => 'Company A', 'slug' => 'company-a']);
        $companyB = Company::create(['name' => 'Company B', 'slug' => 'company-b']);
        $userB = User::factory()->create([
            'company_id' => $companyB->id,
            'email' => 'admin@b.com',
        ]);
        $tokenB = $userB->createToken('auth_token')->plainTextToken;

        $productA = Product::create([
            'company_id' => $companyA->id,
            'name' => 'Product of A',
            'sku' => 'A-SKU-3',
            'price' => 2000,
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'company-b',
            'Authorization' => 'Bearer ' . $tokenB,
        ])->putJson('/api/v1/products/' . $productA->id, [
            'name' => 'Hacked Name',
            'sku' => 'HACKED',
            'price' => 999,
        ]);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    });

    it('cross-tenant delete returns 404', function () {
        $companyA = Company::create(['name' => 'Company A', 'slug' => 'company-a']);
        $companyB = Company::create(['name' => 'Company B', 'slug' => 'company-b']);
        $userB = User::factory()->create([
            'company_id' => $companyB->id,
            'email' => 'admin@b.com',
        ]);
        $tokenB = $userB->createToken('auth_token')->plainTextToken;

        $productA = Product::create([
            'company_id' => $companyA->id,
            'name' => 'Product of A',
            'sku' => 'A-SKU-4',
            'price' => 1500,
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'company-b',
            'Authorization' => 'Bearer ' . $tokenB,
        ])->deleteJson('/api/v1/products/' . $productA->id);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    });

});
