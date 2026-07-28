<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

describe('Delivery Note API', function () {

    function dnAuthUser(string $slug = 'acme'): array
    {
        $company = Company::factory()->create(['slug' => $slug]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => "admin@{$slug}.com",
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        return [$company, $user, $token];
    }

    it('lists delivery notes', function () {
        [$company, $user, $token] = dnAuthUser();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        DeliveryNote::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/delivery-notes');

        $response->assertStatus(Response::HTTP_OK);
    });

    it('creates a delivery note', function () {
        [$company, $user, $token] = dnAuthUser();
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'name' => 'DN Customer',
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/delivery-notes', [
            'customer_id' => $customer->id,
            'issue_date' => '2026-05-21',
            'items' => [
                [
                    'description' => 'Item A',
                    'quantity' => 5,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure(['uuid', 'number', 'status']);

        $this->assertDatabaseHas('delivery_notes', ['company_id' => $company->id]);
    });

    it('shows a delivery note', function () {
        [$company, $user, $token] = dnAuthUser();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $dn = DeliveryNote::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/delivery-notes/' . $dn->id);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['uuid', 'number', 'status', 'customer']);
    });

    it('updates a delivery note', function () {
        [$company, $user, $token] = dnAuthUser();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $dn = DeliveryNote::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1/delivery-notes/' . $dn->id, [
            'customer_id' => $customer->id,
            'issue_date' => '2026-05-21',
            'items' => [
                [
                    'description' => 'Updated item',
                    'quantity' => 3,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
    });

    it('deletes a delivery note (soft delete)', function () {
        [$company, $user, $token] = dnAuthUser();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $dn = DeliveryNote::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/v1/delivery-notes/' . $dn->id);

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted('delivery_notes', ['id' => $dn->id]);
    });

    it('links delivery note to invoice', function () {
        [$company, $user, $token] = dnAuthUser();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/delivery-notes', [
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'issue_date' => '2026-05-21',
            'items' => [
                [
                    'description' => 'Linked item',
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('delivery_notes', [
            'invoice_id' => $invoice->id,
        ]);
    });

    it('returns 401 for unauthenticated access', function () {
        $response = $this->getJson('/api/v1/delivery-notes');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    });

});
