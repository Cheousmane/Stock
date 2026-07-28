<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

describe('Payment API', function () {

    function paymentAuthUser(string $slug = 'acme'): array
    {
        $company = Company::factory()->create(['slug' => $slug]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => "admin@{$slug}.com",
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        return [$company, $user, $token];
    }

    it('records a full payment against an invoice', function () {
        [$company, $user, $token] = paymentAuthUser();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'status' => 'sent',
            'total_xof' => 59000,
            'paid_xof' => 0,
            'balance_due_xof' => 59000,
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id,
            'amount_xof' => 59000,
            'method' => 'cash',
            'payment_date' => '2026-05-22',
        ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
            'paid_xof' => 59000,
            'balance_due_xof' => 0,
        ]);
    });

    it('records a partial payment without marking invoice as paid', function () {
        [$company, $user, $token] = paymentAuthUser();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'status' => 'sent',
            'total_xof' => 35400,
            'paid_xof' => 0,
            'balance_due_xof' => 35400,
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id,
            'amount_xof' => 10000,
            'method' => 'bank',
            'payment_date' => '2026-05-22',
        ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'sent',
            'paid_xof' => 10000,
            'balance_due_xof' => 25400,
        ]);
    });

    it('lists payments', function () {
        [$company, $user, $token] = paymentAuthUser();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'total_xof' => 10000,
            'paid_xof' => 0,
            'balance_due_xof' => 10000,
        ]);

        $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id,
            'amount_xof' => 10000,
            'method' => 'cash',
            'payment_date' => '2026-05-22',
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/payments');

        $response->assertStatus(Response::HTTP_OK);
    });

    it('returns 401 for unauthenticated access', function () {
        $response = $this->postJson('/api/v1/payments', [
            'invoice_id' => 1,
            'amount_xof' => 1000,
            'method' => 'cash',
            'payment_date' => '2026-05-22',
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    });

});
