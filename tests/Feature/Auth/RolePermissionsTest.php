<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Policies\PosSalePolicy;
use App\Policies\PosSessionPolicy;
use App\Policies\SupplierPaymentPolicy;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([PermissionSeeder::class]);
    $this->company = Company::factory()->create(['slug' => 'roles-corp']);
    $this->seed([RoleSeeder::class]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->company->id);
});

function makeRoleUser(string $role, string $email): User
{
    $user = User::factory()->create([
        'company_id' => test()->company->id,
        'email' => $email,
    ]);
    $user->assignRole($role);

    return $user;
}

describe('Role permission matrix', function () {
    it('registers the POS and supplier-payment policies', function () {
        expect(Gate::getPolicyFor(PosSession::class))->toBeInstanceOf(PosSessionPolicy::class);
        expect(Gate::getPolicyFor(PosSale::class))->toBeInstanceOf(PosSalePolicy::class);
        expect(Gate::getPolicyFor(SupplierPayment::class))->toBeInstanceOf(SupplierPaymentPolicy::class);
    });

    it('grants admins everything', function () {
        $user = makeRoleUser('admin', 'admin@roles-corp.com');

        foreach (['access_pos', 'view_capital', 'view_profits', 'export_data', 'manage_users', 'delete_invoice'] as $permission) {
            expect($user->hasPermissionTo($permission))->toBeTrue($permission);
        }
    });

    it('scopes managers to operations plus capital and POS', function () {
        $user = makeRoleUser('manager', 'manager@roles-corp.com');

        foreach (['access_pos', 'view_capital', 'validate_quote', 'convert_quote', 'send_invoice', 'export_data', 'manage_settings'] as $permission) {
            expect($user->hasPermissionTo($permission))->toBeTrue($permission);
        }
        foreach (['view_profits', 'delete_invoice', 'delete_quote', 'manage_users'] as $permission) {
            expect($user->hasPermissionTo($permission))->toBeFalse($permission);
        }
    });

    it('scopes accountants to finance plus capital and POS', function () {
        $user = makeRoleUser('accountant', 'accountant@roles-corp.com');

        foreach (['access_pos', 'view_capital', 'view_profits', 'send_invoice', 'validate_invoice', 'create_payment'] as $permission) {
            expect($user->hasPermissionTo($permission))->toBeTrue($permission);
        }
        foreach (['delete_invoice', 'create_product', 'adjust_stock'] as $permission) {
            expect($user->hasPermissionTo($permission))->toBeFalse($permission);
        }
    });

    it('gives warehouse managers POS without finance', function () {
        $user = makeRoleUser('warehouse_manager', 'warehouse@roles-corp.com');

        expect($user->hasPermissionTo('access_pos'))->toBeTrue();
        expect($user->hasPermissionTo('adjust_stock'))->toBeTrue();
        foreach (['view_capital', 'create_invoice', 'view_profits'] as $permission) {
            expect($user->hasPermissionTo($permission))->toBeFalse($permission);
        }
    });

    it('lets sales sell, send and convert but not supervise', function () {
        $user = makeRoleUser('sales', 'sales@roles-corp.com');

        foreach (['access_pos', 'send_invoice', 'validate_quote', 'convert_quote', 'export_data'] as $permission) {
            expect($user->hasPermissionTo($permission))->toBeTrue($permission);
        }
        foreach (['view_capital', 'delete_invoice', 'manage_users'] as $permission) {
            expect($user->hasPermissionTo($permission))->toBeFalse($permission);
        }
    });

    it('keeps employees strictly read-only', function () {
        $user = makeRoleUser('employee', 'employee@roles-corp.com');

        foreach (['view_dashboard', 'view_invoice', 'view_product'] as $permission) {
            expect($user->hasPermissionTo($permission))->toBeTrue($permission);
        }
        foreach (['access_pos', 'export_data', 'create_invoice', 'view_capital'] as $permission) {
            expect($user->hasPermissionTo($permission))->toBeFalse($permission);
        }
    });
});
