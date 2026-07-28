<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Invoice model', function () {

    it('belongs to company', function () {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        expect($invoice->company)->toBeInstanceOf(Company::class);
        expect($invoice->company->id)->toBe($company->id);
    });

    it('belongs to customer', function () {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        expect($invoice->customer)->toBeInstanceOf(Customer::class);
        expect($invoice->customer->id)->toBe($customer->id);
    });

    it('has many items', function () {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);
        $item = InvoiceItem::factory()->create([
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
        ]);

        expect($invoice->items)->toHaveCount(1);
        expect($invoice->items->first())->toBeInstanceOf(InvoiceItem::class);
    });

    it('has many payments', function () {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);
        $payment = Payment::factory()->create([
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
        ]);

        expect($invoice->payments)->toHaveCount(1);
        expect($invoice->payments->first())->toBeInstanceOf(Payment::class);
    });

    it('casts subtotal_xof as integer', function () {
        $invoice = new Invoice;
        $casts = $invoice->getCasts();

        expect($casts['subtotal_xof'])->toBe('integer');
    });

    it('casts tax_xof as integer', function () {
        $invoice = new Invoice;
        $casts = $invoice->getCasts();

        expect($casts['tax_xof'])->toBe('integer');
    });

    it('casts discount_xof as integer', function () {
        $invoice = new Invoice;
        $casts = $invoice->getCasts();

        expect($casts['discount_xof'])->toBe('integer');
    });

    it('casts total_xof as integer', function () {
        $invoice = new Invoice;
        $casts = $invoice->getCasts();

        expect($casts['total_xof'])->toBe('integer');
    });

    it('casts metadata as array', function () {
        $invoice = new Invoice;
        $casts = $invoice->getCasts();

        expect($casts['metadata'])->toBe('array');
    });

});
