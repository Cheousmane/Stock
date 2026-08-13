<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

describe('Public document verification', function () {

    it('verifies an existing invoice by uuid', function () {
        $company = Company::factory()->create(['name' => 'ACME SARL']);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'number' => 'INV-2026-0001',
            'total_xof' => 125000,
        ]);

        $this->get("/verify/invoice/{$invoice->uuid}")
            ->assertStatus(Response::HTTP_OK)
            ->assertSee('ACME SARL')
            ->assertSee('INV-2026-0001')
            ->assertSee('125 000');
    });

    it('returns 404 for unknown uuid', function () {
        $this->get('/verify/invoice/00000000-0000-4000-8000-000000000000')
            ->assertStatus(Response::HTTP_NOT_FOUND);
    });

    it('does not expose other tenants documents', function () {
        $companyA = Company::factory()->create(['name' => 'Alpha']);
        $companyB = Company::factory()->create(['name' => 'Beta']);
        $customer = Customer::factory()->create(['company_id' => $companyA->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $companyA->id,
            'customer_id' => $customer->id,
        ]);

        $this->get("/verify/invoice/{$invoice->uuid}")
            ->assertStatus(Response::HTTP_OK)
            ->assertSee('Alpha');
    });

});
