<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

describe('Quote API', function () {

    function quoteAuthUser(string $slug = 'acme'): array
    {
        $company = Company::factory()->create(['slug' => $slug]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => "admin@{$slug}.com",
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        return [$company, $user, $token];
    }

    it('lists quotes', function () {
        [$company, $user, $token] = quoteAuthUser();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        Quote::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/quotes');

        $response->assertStatus(Response::HTTP_OK);
    });

    it('creates a quote', function () {
        [$company, $user, $token] = quoteAuthUser();
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'name' => 'Quote Customer',
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/quotes', [
            'customer_id' => $customer->id,
            'issue_date' => '2026-05-21',
            'expiration_date' => '2026-06-21',
            'items' => [
                [
                    'description' => 'Consulting',
                    'quantity' => 1,
                    'unit_price_xof' => 50000,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure(['uuid', 'number', 'status']);

        $this->assertDatabaseHas('quotes', ['company_id' => $company->id]);
    });

    it('shows a quote', function () {
        [$company, $user, $token] = quoteAuthUser();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $quote = Quote::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/quotes/' . $quote->id);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['uuid', 'number', 'status', 'customer']);
    });

    it('updates a quote', function () {
        [$company, $user, $token] = quoteAuthUser();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $quote = Quote::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1/quotes/' . $quote->id, [
            'customer_id' => $customer->id,
            'issue_date' => '2026-05-21',
            'expiration_date' => '2026-06-21',
            'items' => [
                [
                    'description' => 'Updated service',
                    'quantity' => 2,
                    'unit_price_xof' => 25000,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
    });

    it('deletes a quote (soft delete)', function () {
        [$company, $user, $token] = quoteAuthUser();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $quote = Quote::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/v1/quotes/' . $quote->id);

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted('quotes', ['id' => $quote->id]);
    });

    it('converts quote to invoice', function () {
        [$company, $user, $token] = quoteAuthUser();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $quote = Quote::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'status' => 'draft',
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/quotes/{$quote->id}/convert-to-invoice");

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure(['uuid', 'number', 'status']);
    });

    it('marks quote as sent', function () {
        [$company, $user, $token] = quoteAuthUser();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $quote = Quote::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'status' => 'draft',
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->patchJson("/api/v1/quotes/{$quote->id}/mark-as-sent");

        $response->assertStatus(Response::HTTP_OK);
        expect($response->json('status'))->toBe('sent');
    });

    it('returns 401 for unauthenticated access', function () {
        $response = $this->getJson('/api/v1/quotes');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    });

});
