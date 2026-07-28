<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Tax;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Customer model', function () {

    it('belongs to company', function () {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        expect($customer->company)->toBeInstanceOf(Company::class);
        expect($customer->company->id)->toBe($company->id);
    });

    it('has many invoices', function () {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);
        Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        expect($customer->invoices)->toHaveCount(2);
        expect($customer->invoices->first())->toBeInstanceOf(Invoice::class);
    });

});
