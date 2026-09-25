<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Auth\RegisterCompanyAction;
use App\DTOs\RegisterCompanyDTO;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

describe('Trial lifecycle (14-day trial, blocking, fallback)', function () {

    function trialAuthUser(Company $company, array $userOverrides = []): string
    {
        $user = User::factory()->create(array_merge([
            'company_id' => $company->id,
            'email' => 'admin-' . $company->id . '@test.com',
        ], $userOverrides));
        $token = $user->createToken('auth_token')->plainTextToken;

        test()->withHeaders([
            'X-Tenant-Slug' => $company->slug,
            'Authorization' => 'Bearer ' . $token,
        ]);

        return $token;
    }

    function makePlan(string $slug, array $quotas): Plan
    {
        return Plan::factory()->create([
            'slug' => $slug,
            'quotas' => $quotas,
        ]);
    }

    it('puts a new registration on a 14-day trial with full access', function () {
        $pro = makePlan('pro', ['max_products' => 100]);

        $result = app(RegisterCompanyAction::class)->execute(new RegisterCompanyDTO(
            companyName: 'Trial Co',
            companySlug: 'trial-co',
            userName: 'Owner',
            userEmail: 'owner@trial-co.com',
            userPassword: 'password123',
            planId: $pro->id,
        ));

        $company = $result['company']->fresh();
        expect($company->status)->toBe('trial');
        expect($company->plan_id)->toBe($pro->id);
        expect($company->trial_ends_at)->not->toBeNull();
        expect($company->trial_ends_at->isFuture())->toBeTrue();
        expect($company->trial_ends_at->diffInDays(now(), true))->toBeGreaterThanOrEqual(13);
    });

    it('lets a trial company create beyond plan quotas (full access)', function () {
        $plan = makePlan('strict', ['max_products' => 0]);
        $company = Company::factory()->create([
            'slug' => 'trial-full',
            'plan_id' => $plan->id,
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);
        trialAuthUser($company);

        $response = test()->postJson('/api/v1/products', [
            'name' => 'Trial Product',
            'sku' => 'TRIAL-001',
            'price' => 1000,
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
    });

    it('blocks creation with 402 when the trial expired without subscription', function () {
        $plan = makePlan('strict2', ['max_products' => 100]);
        $company = Company::factory()->create([
            'slug' => 'trial-expired',
            'plan_id' => $plan->id,
            'status' => 'trial',
            'trial_ends_at' => now()->subDay(),
        ]);
        trialAuthUser($company);

        $response = test()->postJson('/api/v1/products', [
            'name' => 'Blocked Product',
            'sku' => 'BLOCK-001',
            'price' => 1000,
        ]);

        $response->assertStatus(Response::HTTP_PAYMENT_REQUIRED);
    });

    it('suspends expired paid trials and frees expired free trials', function () {
        $pro = makePlan('pro2', ['max_products' => 100]);
        $free = makePlan('free', ['max_products' => 5]);

        $paid = Company::factory()->create([
            'slug' => 'paid-expired', 'plan_id' => $pro->id,
            'status' => 'trial', 'trial_ends_at' => now()->subDay(),
        ]);
        $freemium = Company::factory()->create([
            'slug' => 'free-expired', 'plan_id' => $free->id,
            'status' => 'trial', 'trial_ends_at' => now()->subDay(),
        ]);

        $this->artisan('companies:manage-statuses')->assertSuccessful();

        expect($paid->fresh()->status)->toBe('suspended');
        expect($paid->fresh()->suspended_at)->not->toBeNull();
        expect($freemium->fresh()->status)->toBe('active');
        expect($freemium->fresh()->trial_ends_at)->toBeNull();
    });

    it('does not suspend an expired trial covered by a billing subscription', function () {
        $pro = makePlan('pro3', ['max_products' => 100]);
        $company = Company::factory()->create([
            'slug' => 'covered', 'plan_id' => $pro->id,
            'status' => 'trial', 'trial_ends_at' => now()->subDay(),
        ]);
        Subscription::create([
            'company_id' => $company->id,
            'plan_id' => $pro->id,
            'type' => 'default',
            'stripe_id' => 'sub_covered',
            'stripe_status' => 'active',
            'quantity' => 1,
        ]);

        $this->artisan('companies:manage-statuses')->assertSuccessful();

        $fresh = $company->fresh();
        expect($fresh->status)->toBe('active');
        expect($fresh->trial_ends_at)->toBeNull();
    });

    it('falls back to free plan when a canceled subscription grace period ends', function () {
        $pro = makePlan('pro4', ['max_products' => 100]);
        $free = makePlan('free2', []);
        // Le scheduler cherche le slug 'free' : on l'aligne.
        $free->update(['slug' => 'free']);
        $company = Company::factory()->create([
            'slug' => 'grace-ended', 'plan_id' => $pro->id, 'status' => 'active',
        ]);
        Subscription::create([
            'company_id' => $company->id,
            'plan_id' => $pro->id,
            'type' => 'default',
            'stripe_id' => 'sub_ended',
            'stripe_status' => 'canceled',
            'quantity' => 1,
            'ends_at' => now()->subDay(),
        ]);

        $this->artisan('companies:manage-statuses')->assertSuccessful();

        $fresh = $company->fresh();
        expect($fresh->plan_id)->toBe($free->id);
        expect($fresh->status)->toBe('active');
    });

    it('lets a super admin activate a company and settle trial state', function () {
        $pro = makePlan('pro5', ['max_products' => 100]);
        $adminCompany = Company::factory()->create(['slug' => 'admin-co']);
        trialAuthUser($adminCompany, ['is_super_admin' => true]);

        $target = Company::factory()->create([
            'slug' => 'to-activate', 'plan_id' => $pro->id,
            'status' => 'suspended', 'trial_ends_at' => now()->subDay(),
            'metadata' => ['suspended_until' => now()->addDay()->toDateString()],
        ]);

        $response = test()->postJson("/api/v1/admin/companies/{$target->id}/activate");

        $response->assertStatus(Response::HTTP_OK);
        $fresh = $target->fresh();
        expect($fresh->status)->toBe('active');
        expect($fresh->suspended_at)->toBeNull();
        expect($fresh->trial_ends_at)->toBeNull();
        expect($fresh->metadata['suspended_until'] ?? null)->toBeNull();
    });

    it('lets a super admin permanently delete a company and its data', function () {
        $adminCompany = Company::factory()->create(['slug' => 'admin-del']);
        trialAuthUser($adminCompany, ['is_super_admin' => true]);

        $target = Company::factory()->create(['slug' => 'doomed']);
        $user = User::factory()->create(['company_id' => $target->id]);
        $token = $user->createToken('t')->plainTextToken;
        \App\Models\Product::factory()->create(['company_id' => $target->id]);
        \App\Models\Customer::factory()->create(['company_id' => $target->id]);
        $role = \Spatie\Permission\Models\Role::create([
            'name' => 'tmp', 'guard_name' => 'web', 'company_id' => $target->id,
        ]);

        $response = test()->deleteJson("/api/v1/admin/companies/{$target->id}");

        $response->assertStatus(Response::HTTP_OK);
        expect(Company::find($target->id))->toBeNull();
        expect(User::where('company_id', $target->id)->count())->toBe(0);
        expect(\App\Models\Product::where('company_id', $target->id)->count())->toBe(0);
        expect(\App\Models\Customer::where('company_id', $target->id)->count())->toBe(0);
        expect(\Spatie\Permission\Models\Role::where('company_id', $target->id)->count())->toBe(0);
        expect(\Laravel\Sanctum\PersonalAccessToken::where('tokenable_type', User::class)->where('tokenable_id', $user->id)->count())->toBe(0);
    });

    it('refuses deletion of its own company', function () {
        $adminCompany = Company::factory()->create(['slug' => 'admin-own']);
        trialAuthUser($adminCompany, ['is_super_admin' => true]);

        $response = test()->deleteJson("/api/v1/admin/companies/{$adminCompany->id}");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        expect(Company::find($adminCompany->id))->not->toBeNull();
    });

    it('forbids company deletion for non admins', function () {
        $company = Company::factory()->create(['slug' => 'plain-co']);
        trialAuthUser($company);
        $target = Company::factory()->create(['slug' => 'victim']);

        $response = test()->deleteJson("/api/v1/admin/companies/{$target->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
        expect(Company::find($target->id))->not->toBeNull();
    });

    it('lets super admins bypass quotas', function () {
        $plan = makePlan('strict3', ['max_products' => 0]);
        $company = Company::factory()->create([
            'slug' => 'superadmin-co', 'plan_id' => $plan->id, 'status' => 'active',
        ]);
        trialAuthUser($company, ['is_super_admin' => true]);

        $response = test()->postJson('/api/v1/products', [
            'name' => 'Super Product',
            'sku' => 'SUPER-001',
            'price' => 1000,
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
    });
});
