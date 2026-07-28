<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Billable;
use Spatie\Permission\Traits\HasRoles;

uses(RefreshDatabase::class);

describe('User model', function () {

    it('has uuid on create', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        expect($user->uuid)->not->toBeNull();
        expect($user->uuid)->toBeString();
    });

    it('belongs to company', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        expect($user->company)->toBeInstanceOf(Company::class);
        expect($user->company->id)->toBe($company->id);
    });

    it('uses Billable trait', function () {
        $traits = class_uses(User::class);
        expect(in_array(Billable::class, $traits))->toBeTrue();
    });

    it('uses HasRoles trait', function () {
        $traits = class_uses(User::class);
        expect(in_array(HasRoles::class, $traits))->toBeTrue();
    });

    it('has fillable attributes', function () {
        $user = new User;

        $fillable = $user->getFillable();

        expect($fillable)->toContain('name');
        expect($fillable)->toContain('email');
        expect($fillable)->toContain('password');
        expect($fillable)->toContain('company_id');
    });

});
