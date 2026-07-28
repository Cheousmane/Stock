<?php

declare(strict_types=1);

namespace Tests\Feature\Api\v1;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

describe('Invoice', function () {

    function invoiceAuthUser(string $slug = 'acme'): array
    {
        $company = Company::factory()->create(['slug' => $slug]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => "admin@{$slug}.com",
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        return [$company, $user, $token];
    }

    function invoiceHeaders(string $slug, string $token): array
    {
        return [
            'X-Tenant-Slug' => $slug,
            'Authorization' => 'Bearer ' . $token,
        ];
    }

    it('lists invoices with filters', function () {
        [$company, $user, $token] = invoiceAuthUser();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'status' => 'draft',
        ]);

        $response = $this->withHeaders(invoiceHeaders('acme', $token))
            ->getJson('/api/v1/invoices');

        $response->assertStatus(Response::HTTP_OK);
    });

    it('creates an invoice with items', function () {
        [$company, $user, $token] = invoiceAuthUser();
        $headers = invoiceHeaders('acme', $token);

        \App\Models\Warehouse::create([
            'company_id' => $company->id,
            'name' => 'Main Warehouse',
            'code' => 'WH-001',
        ]);

        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'name' => 'Invoice Customer',
        ]);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Widget',
            'sku' => 'WDG-001',
            'price' => 5000,
        ]);

        $response = $this->withHeaders($headers)
            ->postJson('/api/v1/invoices', [
                'customer_id' => $customer->id,
                'issue_date' => '2026-05-21',
                'due_date' => '2026-06-21',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'description' => 'Widget x 2',
                        'quantity' => 2,
                        'unit_price_xof' => 5000,
                        'tax_rate' => 18,
                    ],
                ],
                'notes' => 'Test invoice',
            ]);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure(['uuid', 'number', 'status', 'items']);

        $this->assertDatabaseHas('invoices', ['company_id' => $company->id]);
        $this->assertDatabaseHas('invoice_items', ['company_id' => $company->id]);
    });

    it('shows an invoice with items', function () {
        [$company, $user, $token] = invoiceAuthUser();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->withHeaders(invoiceHeaders('acme', $token))
            ->getJson('/api/v1/invoices/' . $invoice->id);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['uuid', 'number', 'status', 'customer']);
    });

    it('updates an invoice', function () {
        [$company, $user, $token] = invoiceAuthUser();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->withHeaders(invoiceHeaders('acme', $token))
            ->putJson('/api/v1/invoices/' . $invoice->id, [
                'customer_id' => $customer->id,
                'issue_date' => '2026-05-21',
                'due_date' => '2026-06-21',
                'items' => [
                    [
                        'description' => 'Updated item',
                        'quantity' => 1,
                        'unit_price_xof' => 3000,
                    ],
                ],
            ]);

        $response->assertStatus(Response::HTTP_OK);
    });

    it('deletes an invoice (soft delete)', function () {
        [$company, $user, $token] = invoiceAuthUser();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->withHeaders(invoiceHeaders('acme', $token))
            ->deleteJson('/api/v1/invoices/' . $invoice->id);

        $response->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    });

    it('marks invoice as sent', function () {
        [$company, $user, $token] = invoiceAuthUser();
        $headers = invoiceHeaders('acme', $token);

        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'status' => 'draft',
        ]);

        $response = $this->withHeaders($headers)
            ->patchJson("/api/v1/invoices/{$invoice->id}/mark-as-sent");

        $response->assertStatus(Response::HTTP_OK);
        expect($response->json('status'))->toBe('sent');
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'sent',
        ]);
    });

    it('cancels an invoice', function () {
        [$company, $user, $token] = invoiceAuthUser();
        $headers = invoiceHeaders('acme', $token);

        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'status' => 'draft',
        ]);

        $response = $this->withHeaders($headers)
            ->patchJson("/api/v1/invoices/{$invoice->id}/mark-as-cancelled");

        $response->assertStatus(Response::HTTP_OK);
        expect($response->json('status'))->toBe('cancelled');
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'cancelled',
        ]);
    });

    it('payment on invoice reduces balance and marks as paid', function () {
        [$company, $user, $token] = invoiceAuthUser();
        $headers = invoiceHeaders('acme', $token);

        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'name' => 'Paying Customer',
            'email' => 'paying@test.com',
        ]);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'number' => 'INV-002',
            'status' => 'sent',
            'issue_date' => '2026-05-21',
            'due_date' => '2026-06-21',
            'subtotal_xof' => 50000,
            'tax_xof' => 9000,
            'total_xof' => 59000,
            'paid_xof' => 0,
            'balance_due_xof' => 59000,
        ]);

        $paymentResponse = $this->withHeaders($headers)
            ->postJson('/api/v1/payments', [
                'invoice_id' => $invoice->id,
                'amount_xof' => 59000,
                'method' => 'cash',
                'payment_date' => '2026-05-22',
            ]);

        $paymentResponse->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
            'paid_xof' => 59000,
            'balance_due_xof' => 0,
        ]);
    });

    it('partial payment reduces balance without marking as paid', function () {
        [$company, $user, $token] = invoiceAuthUser();
        $headers = invoiceHeaders('acme', $token);

        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'name' => 'Partial Payer',
            'email' => 'partial@test.com',
        ]);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'number' => 'INV-003',
            'status' => 'sent',
            'issue_date' => '2026-05-21',
            'due_date' => '2026-06-21',
            'subtotal_xof' => 30000,
            'tax_xof' => 5400,
            'total_xof' => 35400,
            'paid_xof' => 0,
            'balance_due_xof' => 35400,
        ]);

        $paymentResponse = $this->withHeaders($headers)
            ->postJson('/api/v1/payments', [
                'invoice_id' => $invoice->id,
                'amount_xof' => 10000,
                'method' => 'bank',
                'payment_date' => '2026-05-22',
            ]);

        $paymentResponse->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'sent',
            'paid_xof' => 10000,
            'balance_due_xof' => 25400,
        ]);
    });

    it('returns 401 for unauthenticated access', function () {
        $response = $this->getJson('/api/v1/invoices');
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    });

});
