<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Auth\RegisterCompanyAction;
use App\DTOs\RegisterCompanyDTO;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use App\Services\QuotaService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

describe('Freemium trial lifecycle (auto free plan + 14-day trial)', function () {

    function superAdminAuth(Company $company, array $userOverrides = []): string
    {
        $user = User::factory()->create(array_merge([
            'company_id' => $company->id,
            'email' => 'admin-' . $company->id . '@test.com',
            'is_super_admin' => true,
        ], $userOverrides));
        $token = $user->createToken('auth_token')->plainTextToken;

        test()->withHeaders([
            'X-Tenant-Slug' => $company->slug,
            'Authorization' => 'Bearer ' . $token,
        ]);

        return $token;
    }

    function freePlan(int $maxProducts = 10): Plan
    {
        return Plan::factory()->create([
            'slug' => 'free',
            'price_xof' => 0,
            'quotas' => [
                'max_users' => 5,
                'max_products' => $maxProducts,
                'max_invoices' => 5,
                'max_warehouses' => 1,
            ],
        ]);
    }

    function starterPlan(): Plan
    {
        return Plan::factory()->create([
            'slug' => 'starter',
            'price_xof' => 15000,
            'quotas' => [
                'max_users' => 10,
                'max_products' => 100,
                'max_invoices' => 50,
                'max_warehouses' => 2,
            ],
        ]);
    }

    function registerNewTenant(): array
    {
        // Un plan Free doit exister en base (source de verite des quotas).
        freePlan();

        $result = app(RegisterCompanyAction::class)->execute(new RegisterCompanyDTO(
            companyName: 'Trial Co',
            companySlug: 'trial-co',
            userName: 'Owner',
            userEmail: 'owner@trial-co.com',
            userPassword: 'password123',
        ));

        return [$result['company']->fresh(), $result['user']];
    }

    function authTenantUser(Company $company): string
    {
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'admin-' . $company->id . '@test.com',
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        test()->withHeaders([
            'X-Tenant-Slug' => $company->slug,
            'Authorization' => 'Bearer ' . $token,
        ]);

        return $token;
    }

    it('assigns every new registration to a pending (unverified) account without starting the trial', function () {
        [$company, $user] = registerNewTenant();

        expect($company->status)->toBe('pending')
            ->and($company->plan_id)->toBeNull()
            ->and($company->trial_started_at)->toBeNull()
            ->and($company->trial_ends_at)->toBeNull()
            ->and($company->subscriptions()->count())->toBe(0)
            ->and($user->fresh()->email_verified_at)->toBeNull()
            ->and($user->fresh()->frozen_at)->toBeNull();
    });

    it('gives full access during the trial even beyond the free quotas', function () {
        [$company, $user] = registerNewTenant();

        // Vérification de l'e-mail => activation du compte + démarrage de l'essai.
        $user->forceFill(['email_verified_at' => now()])->save();
        app(SubscriptionService::class)->startTrial($company);

        $company = $company->fresh();
        $user = $user->fresh();

        Product::factory()->count(2)->create(['company_id' => $company->id]);

        $token = $user->createToken('auth_token')->plainTextToken;
        test()->withHeaders([
            'X-Tenant-Slug' => $company->slug,
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response = test()->postJson('/api/v1/products', [
            'name' => 'Trial Product',
            'sku' => 'TRIAL-001',
            'price' => 1000,
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        expect(Product::where('company_id', $company->id)->count())->toBe(3);
    });

    it('expires the trial and freezes the excess data when the free quota is exceeded', function () {
        $free = freePlan(1);
        $company = Company::factory()->create([
            'slug' => 'trial-expired-co',
            'plan_id' => $free->id,
            'status' => 'trial',
            'trial_started_at' => now()->subDays(16),
            'trial_ends_at' => now()->subDay(),
        ]);

        Product::factory()->count(2)->create(['company_id' => $company->id]);

        $token = authTenantUser($company);
        test()->withHeaders([
            'X-Tenant-Slug' => $company->slug,
            'Authorization' => 'Bearer ' . $token,
        ]);

        $this->artisan('companies:manage-statuses')->assertSuccessful();

        $fresh = $company->fresh();
        expect($fresh->status)->toBe('active')
            ->and($fresh->plan_id)->toBe($free->id)
            ->and($fresh->trial_ends_at)->not->ToBeNull()
            ->and($fresh->trial_ends_at->isPast())->toBeTrue();

        $response = test()->postJson('/api/v1/products', [
            'name' => 'Should Be Blocked',
            'sku' => 'BLOCK-001',
            'price' => 1000,
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        expect(app(QuotaService::class)->frozenCount($company, 'products'))->toBe(1);
    });

    it('unfreezes frozen data on upgrade and re-freezes on downgrade to free', function () {
        $free = freePlan(1);
        $starter = starterPlan();
        $company = Company::factory()->create([
            'slug' => 'upgrade-co',
            'plan_id' => $free->id,
            'status' => 'active',
        ]);

        // Free quota : 1 produit -> le second est gele.
        Product::factory()->count(2)->create(['company_id' => $company->id]);
        app(SubscriptionService::class)->syncFreezing($company);
        expect(app(QuotaService::class)->frozenCount($company, 'products'))->toBe(1);

        $adminCompany = Company::factory()->create(['slug' => 'admin-up-co']);
        superAdminAuth($adminCompany);

        // Upgrade : le gel est leve.
        $assign = test()->postJson("/api/v1/admin/subscriptions/{$company->id}/assign", [
            'plan_id' => $starter->id,
        ]);
        $assign->assertStatus(Response::HTTP_OK);
        expect(app(QuotaService::class)->frozenCount($company, 'products'))->toBe(0);

        // Downgrade vers le Free : les excements sont a nouveau gels.
        $downgrade = test()->postJson("/api/v1/admin/subscriptions/{$company->id}/assign", [
            'plan_id' => $free->id,
        ]);
        $downgrade->assertStatus(Response::HTTP_OK);

        // Deux produits existent (quota Free de 1) : le second est gele a nouveau.
        expect(app(QuotaService::class)->frozenCount($company, 'products'))->toBe(1);

        // Le quota Free est a nouveau operationnel : la creation est bloquee.
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(QuotaService::class)->ensureCanCreate($company, 'products');
    });

    it('blocks a frozen user from using the tenant API', function () {
        $company = Company::factory()->create(['slug' => 'frozen-user-co']);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'frozen@frozen-user-co.com',
            'frozen_at' => now(),
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        test()->withHeaders([
            'X-Tenant-Slug' => $company->slug,
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response = test()->getJson('/api/v1/products');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
        expect($user->fresh()->frozen_at)->not->ToBeNull();
    });

    it('manages trial periods and subscriptions from the super admin panel', function () {
        $adminCompany = Company::factory()->create(['slug' => 'admin-sub-co']);
        superAdminAuth($adminCompany);

        $trialCompany = Company::factory()->create([
            'slug' => 'trial-sub-co',
            'plan_id' => freePlan()->id,
            'status' => 'trial',
            'trial_started_at' => now()->subDays(7),
            'trial_ends_at' => now()->addDays(7),
        ]);

        // Prolongation d'essai
        $extend = test()->postJson("/api/v1/admin/subscriptions/{$trialCompany->id}/trial/extend", [
            'days' => 30,
        ]);
        $extend->assertStatus(Response::HTTP_OK);

        $trialFresh = $trialCompany->fresh();
        expect($trialFresh->status)->toBe('trial')
            ->and($trialFresh->trial_ends_at->diffInDays(now(), true))->toBeGreaterThan(30);

        // Reinitialisation d'essai
        $reset = test()->postJson("/api/v1/admin/subscriptions/{$trialCompany->id}/trial/reset", [
            'days' => 14,
        ]);
        $reset->assertStatus(Response::HTTP_OK);

        $trialReset = $trialCompany->fresh();
        expect($trialReset->status)->toBe('trial')
            ->and($trialReset->trial_started_at)->not->ToBeNull()
            ->and($trialReset->trial_ends_at->diffInDays(now(), true))->toBeGreaterThanOrEqual(13);

        // Attribution d'un plan payant + renouvellement
        $starter = starterPlan();
        $assign = test()->postJson("/api/v1/admin/subscriptions/{$trialCompany->id}/assign", [
            'plan_id' => $starter->id,
        ]);
        $assign->assertStatus(Response::HTTP_OK);

        $renew = test()->postJson("/api/v1/admin/subscriptions/{$trialCompany->id}/renew", [
            'months' => 6,
        ]);
        $renew->assertStatus(Response::HTTP_OK);

        $subscription = Subscription::where('company_id', $trialCompany->id)->latest('id')->first();
        expect($subscription)->not->ToBeNull()
            ->and($subscription->status)->toBe(Subscription::STATUS_ACTIVE)
            ->and($subscription->ends_at)->not->ToBeNull();

        // Suspension / reactivation
        $suspend = test()->postJson("/api/v1/admin/subscriptions/{$trialCompany->id}/suspend", [
            'notes' => 'Panne technique',
        ]);
        $suspend->assertStatus(Response::HTTP_OK);

        $suspended = $trialCompany->fresh();
        expect($suspended->status)->toBe('suspended')
            ->and($suspended->suspended_at)->not->ToBeNull();

        $reactivate = test()->postJson("/api/v1/admin/subscriptions/{$trialCompany->id}/reactivate");
        $reactivate->assertStatus(Response::HTTP_OK);

        $reactivated = $trialCompany->fresh();
        expect($reactivated->status)->toBe('active')
            ->and($reactivated->suspended_at)->toBeNull();

        // Historique des actions super admin
        $show = test()->getJson("/api/v1/admin/subscriptions/{$trialCompany->id}");
        $show->assertStatus(Response::HTTP_OK);

        $history = collect($show->json('data.history') ?? []);
        expect($history->contains(fn ($entry) => $entry['event'] === 'subscription.assigned'))->toBeTrue()
            ->and($history->contains(fn ($entry) => $entry['event'] === 'subscription.suspended'))->toBeTrue()
            ->and($history->contains(fn ($entry) => $entry['event'] === 'subscription.reactivated'))->toBeTrue();
    });
});
