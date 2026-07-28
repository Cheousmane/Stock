<?php

declare(strict_types=1);

namespace Tests\Feature\Api\v1;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

describe('Product', function () {

    function productAuthUser(string $slug = 'acme'): array
    {
        $company = Company::factory()->create(['slug' => $slug]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => "admin@{$slug}.com",
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        return [$company, $user, $token];
    }

    it('lists products (paginated)', function () {
        [$company, $user, $token] = productAuthUser();
        Product::factory()->count(3)->create(['company_id' => $company->id]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/products');

        $response->assertStatus(Response::HTTP_OK);
    });

    it('creates a product', function () {
        [$company, $user, $token] = productAuthUser();

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/products', [
            'name' => 'New Product',
            'sku' => 'NEW-001',
            'price' => 10000,
            'description' => 'A brand new product',
        ]);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonFragment(['name' => 'New Product']);
        $this->assertDatabaseHas('products', [
            'company_id' => $company->id,
            'sku' => 'NEW-001',
        ]);
    });

    it('shows a product', function () {
        [$company, $user, $token] = productAuthUser();
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Visible Product',
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/products/' . $product->id);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment(['name' => 'Visible Product']);
    });

    it('updates a product', function () {
        [$company, $user, $token] = productAuthUser();
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Old Name',
            'sku' => 'OLD-001',
            'price' => 5000,
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1/products/' . $product->id, [
            'name' => 'Updated Name',
            'sku' => 'UPD-001',
            'price' => 7500,
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment(['name' => 'Updated Name']);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Name',
        ]);
    });

    it('deletes a product (soft delete)', function () {
        [$company, $user, $token] = productAuthUser();
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Delete Me',
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/v1/products/' . $product->id);

        $response->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    });

    it('validates required fields on create', function () {
        [$company, $user, $token] = productAuthUser();

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/products', []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name', 'sku', 'price']);
    });

    it('product belongs to tenant and is isolated', function () {
        [$companyA, $userA, $tokenA] = productAuthUser('company-a');

        $productA = Product::factory()->create([
            'company_id' => $companyA->id,
            'name' => 'Product A',
            'sku' => 'PRD-A',
            'price' => 1000,
        ]);

        [$companyB, $userB, $tokenB] = productAuthUser('company-b');

        $showResponse = $this->withHeaders([
            'X-Tenant-Slug' => 'company-b',
            'Authorization' => 'Bearer ' . $tokenB,
        ])->getJson('/api/v1/products/' . $productA->id);

        $showResponse->assertStatus(Response::HTTP_NOT_FOUND);
    });

    it('returns 401 for unauthenticated access', function () {
        $response = $this->getJson('/api/v1/products');
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    });

});
