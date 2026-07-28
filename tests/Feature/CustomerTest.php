<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

describe('Customer API', function () {

    function customerAuthUser(string $slug = 'acme'): array
    {
        $company = Company::factory()->create(['slug' => $slug]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => "admin@{$slug}.com",
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        return [$company, $user, $token];
    }

    it('lists customers', function () {
        [$company, $user, $token] = customerAuthUser();
        Customer::factory()->count(3)->create(['company_id' => $company->id]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/customers');

        $response->assertStatus(Response::HTTP_OK);
    });

    it('creates a customer', function () {
        [$company, $user, $token] = customerAuthUser();

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/customers', [
            'name' => 'New Customer',
            'email' => 'customer@example.com',
            'phone' => '+123456789',
            'city' => 'Douala',
            'country' => 'Cameroon',
        ]);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonFragment(['name' => 'New Customer']);

        $this->assertDatabaseHas('customers', [
            'company_id' => $company->id,
            'email' => 'customer@example.com',
        ]);
    });

    it('shows a customer', function () {
        [$company, $user, $token] = customerAuthUser();
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'name' => 'Visible Customer',
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/customers/' . $customer->id);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment(['name' => 'Visible Customer']);
    });

    it('updates a customer', function () {
        [$company, $user, $token] = customerAuthUser();
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'name' => 'Old Customer',
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1/customers/' . $customer->id, [
            'name' => 'Updated Customer',
            'email' => 'updated@example.com',
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment(['name' => 'Updated Customer']);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Customer',
        ]);
    });

    it('deletes a customer (soft delete)', function () {
        [$company, $user, $token] = customerAuthUser();
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'name' => 'Delete Me',
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/v1/customers/' . $customer->id);

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    });

    it('validates required name on create', function () {
        [$company, $user, $token] = customerAuthUser();

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/customers', []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name']);
    });

    it('returns 401 for unauthenticated access', function () {
        $response = $this->getJson('/api/v1/customers');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    });

});
