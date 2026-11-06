<?php

declare(strict_types=1);

namespace Tests\Feature\Api\v1;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create(['slug' => 'tour-corp']);
    $this->seed([PermissionSeeder::class]);
    $this->seed([RoleSeeder::class]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->company->id);
});

function makeTourUser(string $role, string $email): User
{
    $user = User::factory()->create([
        'company_id' => test()->company->id,
        'email' => $email,
    ]);
    $user->assignRole($role);

    return $user;
}

function tourHeaders(User $user): array
{
    return [
        'X-Tenant-Slug' => test()->company->slug,
        'Authorization' => 'Bearer '.$user->createToken('auth_token')->plainTextToken,
    ];
}

function tourStepIds($response): array
{
    return collect($response->json('steps'))->pluck('id')->all();
}

describe('Guided tour', function () {
    it('builds the complete tour for an administrator with all rights', function () {
        $user = makeTourUser('admin', 'admin@tour-corp.com');

        $response = $this->withHeaders(tourHeaders($user))->getJson('/api/v1/auth/tour');

        $response->assertOk()
            ->assertJsonPath('profile', 'admin')
            ->assertJsonPath('completed', false)
            ->assertJsonPath('needs_refresh', false)
            ->assertJsonPath('should_start', true);

        $ids = tourStepIds($response);

        expect($ids)
            ->toContain('welcome', 'dashboard', 'navigation', 'search', 'quick_create')
            ->toContain('sales', 'invoicing', 'purchasing', 'inventory', 'finance')
            ->toContain('payroll', 'reports', 'team', 'activity', 'settings')
            ->toContain('help', 'profile')
            ->not->toContain('admin_dashboard');
    });

    it('only presents modules allowed for a manager with partial rights', function () {
        $user = makeTourUser('manager', 'manager@tour-corp.com');

        $response = $this->withHeaders(tourHeaders($user))->getJson('/api/v1/auth/tour');

        $response->assertOk()->assertJsonPath('profile', 'manager');

        $ids = tourStepIds($response);

        expect($ids)
            ->toContain('dashboard', 'quick_create', 'sales', 'invoicing', 'inventory', 'finance')
            ->toContain('payroll', 'settings', 'activity')
            ->not->toContain('team', 'reports');
    });

    it('restricts a standard employee to operational read-only modules', function () {
        $user = makeTourUser('employee', 'employee@tour-corp.com');

        $response = $this->withHeaders(tourHeaders($user))->getJson('/api/v1/auth/tour');

        $response->assertOk()->assertJsonPath('profile', 'employee');

        $ids = tourStepIds($response);

        expect($ids)
            ->toContain('dashboard', 'navigation', 'sales', 'invoicing', 'inventory', 'finance')
            ->not->toContain('quick_create', 'payroll', 'reports', 'team', 'activity', 'settings');
    });

    it('rebuilds the tour and flags it when the user permissions change', function () {
        $user = makeTourUser('employee', 'changed@tour-corp.com');
        $headers = tourHeaders($user);

        $this->withHeaders($headers)->postJson('/api/v1/auth/tour/complete')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/auth/tour')
            ->assertOk()
            ->assertJsonPath('completed', true)
            ->assertJsonPath('needs_refresh', false)
            ->assertJsonPath('should_start', false);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->company->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->unsetRelation('roles');
        $user->syncRoles(['admin']);

        $response = $this->withHeaders($headers)->getJson('/api/v1/auth/tour');

        $response->assertOk()
            ->assertJsonPath('profile', 'admin')
            ->assertJsonPath('completed', true)
            ->assertJsonPath('needs_refresh', true)
            ->assertJsonPath('should_start', true);

        expect(tourStepIds($response))->toContain('team', 'settings', 'reports');
    });

    it('clears completion so a manual restart starts from the first step', function () {
        $user = makeTourUser('admin', 'restart@tour-corp.com');
        $headers = tourHeaders($user);

        $this->withHeaders($headers)->postJson('/api/v1/auth/tour/complete')->assertOk();
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'tour_completed_at' => null,
        ]);

        $this->withHeaders($headers)->postJson('/api/v1/auth/tour/restart')
            ->assertOk()
            ->assertJsonPath('completed', false)
            ->assertJsonPath('should_start', true);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'tour_completed_at' => null,
            'tour_completed_hash' => null,
        ]);

        $this->withHeaders($headers)->getJson('/api/v1/auth/tour')
            ->assertOk()
            ->assertJsonPath('should_start', true)
            ->assertJsonPath('steps.0.id', 'welcome');
    });

    it('uses the administration console tour for super administrators', function () {
        $user = makeTourUser('employee', 'super@tour-corp.com');
        $user->forceFill(['is_super_admin' => true])->save();

        $response = $this->withHeaders(tourHeaders($user))->getJson('/api/v1/auth/tour');

        $response->assertOk()
            ->assertJsonPath('profile', 'super_admin')
            ->assertJsonPath('is_super_admin', true);

        expect(tourStepIds($response))
            ->toContain('welcome', 'admin_dashboard', 'admin_navigation', 'admin_companies')
            ->toContain('admin_subscriptions', 'admin_security', 'admin_users', 'profile')
            ->not->toContain('dashboard', 'settings');
    });

    it('requires authentication to access the guided tour', function () {
        $this->getJson('/api/v1/auth/tour')->assertStatus(Response::HTTP_UNAUTHORIZED);
    });
});
