<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Company;
use App\Support\TenantContext;

describe('TenantContext', function () {

    beforeEach(function () {
        app()->instance(TenantContext::class, new TenantContext());
    });

    it('has no company before setting', function () {
        expect(TenantContext::has())->toBeFalse();
        expect(TenantContext::get())->toBeNull();
        expect(TenantContext::getCompanyId())->toBeNull();
    });

    it('can set and get a company', function () {
        $company = new Company(['id' => 1, 'name' => 'Acme', 'slug' => 'acme']);
        $company->id = 1;

        TenantContext::set($company);

        expect(TenantContext::has())->toBeTrue();
        expect(TenantContext::get())->toBe($company);
        expect(TenantContext::getCompanyId())->toBe(1);
    });

    it('can set a different company', function () {
        $companyA = new Company(['id' => 1, 'name' => 'A', 'slug' => 'a']);
        $companyA->id = 1;
        $companyB = new Company(['id' => 2, 'name' => 'B', 'slug' => 'b']);
        $companyB->id = 2;

        TenantContext::set($companyA);
        expect(TenantContext::getCompanyId())->toBe(1);

        TenantContext::set($companyB);
        expect(TenantContext::getCompanyId())->toBe(2);
    });

    it('clears the company context', function () {
        $company = new Company(['id' => 1, 'name' => 'Acme', 'slug' => 'acme']);
        $company->id = 1;

        TenantContext::set($company);
        expect(TenantContext::has())->toBeTrue();

        TenantContext::clear();

        expect(TenantContext::has())->toBeFalse();
        expect(TenantContext::get())->toBeNull();
        expect(TenantContext::getCompanyId())->toBeNull();
    });

    it('clear after no set does not error', function () {
        TenantContext::clear();

        expect(TenantContext::has())->toBeFalse();
    });

});
