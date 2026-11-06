<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * Builds the guided tour of a user from their actual roles and permissions.
 *
 * The service never grants access: it only decides which interface areas can
 * be presented. Every route used by the tour remains protected by the
 * application's usual middleware, policies and permission checks.
 */
class TourService
{
    /**
     * Increment this version whenever the tour catalogue changes in a way
     * that should be proposed again to users who already completed it.
     */
    public const VERSION = 2;

    /**
     * Build the complete payload consumed by the frontend tour host.
     *
     * @return array<string, mixed>
     */
    public function payload(User $user): array
    {
        $this->refreshAccessRelations($user);
        $permissions = $this->permissionsFor($user);
        $roles = $user->getRoleNames()->sort()->values()->all();
        $profile = $this->resolveProfile($user, $roles);
        $hash = $this->signature($user, $roles, $permissions);

        $isCompleted = $user->tour_completed_at !== null;
        $isUpToDate = $isCompleted && hash_equals((string) $user->tour_completed_hash, $hash);

        return [
            'version' => self::VERSION,
            'profile' => $profile,
            'profile_label' => 'appTour.profiles.'.$profile.'.label',
            'roles' => $roles,
            'permissions' => $permissions,
            'is_super_admin' => (bool) $user->is_super_admin,
            'permission_hash' => $hash,
            'completed_at' => $user->tour_completed_at?->toIso8601String(),
            'completed' => $isCompleted,
            'needs_refresh' => $isCompleted && ! $isUpToDate,
            'should_start' => ! $isUpToDate,
            'steps' => $user->is_super_admin
                ? $this->superAdminSteps($profile)
                : $this->userSteps($profile, $permissions),
        ];
    }

    /**
     * Build a stable signature of the user's effective access.
     */
    public function signatureFor(User $user): string
    {
        $this->refreshAccessRelations($user);
        $permissions = $this->permissionsFor($user);
        $roles = $user->getRoleNames()->sort()->values()->all();

        return $this->signature($user, $roles, $permissions);
    }

    private function refreshAccessRelations(User $user): void
    {
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
    }

    /**
     * @return array<int, string>
     */
    private function permissionsFor(User $user): array
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->company_id);

        return $user->getAllPermissions()
            ->pluck('name')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $roles
     */
    private function resolveProfile(User $user, array $roles): string
    {
        if ($user->is_super_admin) {
            return 'super_admin';
        }

        foreach (['admin', 'manager', 'accountant', 'warehouse_manager', 'sales', 'employee'] as $role) {
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }

        return 'custom';
    }

    /**
     * @param array<int, string> $roles
     * @param array<int, string> $permissions
     */
    private function signature(User $user, array $roles, array $permissions): string
    {
        return hash('sha256', json_encode([
            'version' => self::VERSION,
            'super_admin' => (bool) $user->is_super_admin,
            'roles' => $roles,
            'permissions' => $permissions,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<int, string> $permissions
     * @return array<int, array<string, mixed>>
     */
    private function userSteps(string $profile, array $permissions): array
    {
        $home = $this->firstRoute($permissions, [
            'view_dashboard' => '/dashboard',
            'access_pos' => '/pos',
            'view_invoice' => '/invoices',
            'view_product' => '/products',
            'view_customer' => '/customers',
        ]);

        $steps = [$this->introStep($profile)];

        if ($this->can($permissions, 'view_dashboard')) {
            $steps[] = $this->step(
                id: 'dashboard',
                route: '/dashboard',
                selector: '[data-tour="dashboard-kpis"]',
                title: 'appTour.steps.dashboard.title',
                description: 'appTour.steps.dashboard.description',
            );
        }

        $steps[] = $this->step(
            id: 'navigation',
            route: $home,
            selector: '[data-tour="navigation"]',
            title: 'appTour.steps.navigation.title',
            description: 'appTour.steps.navigation.description',
            focusSidebar: true,
        );
        $steps[] = $this->step(
            id: 'search',
            route: $home,
            selector: '[data-tour="search"]',
            title: 'appTour.steps.search.title',
            description: 'appTour.steps.search.description',
        );

        if ($this->canAny($permissions, ['create_invoice', 'create_product', 'create_customer', 'access_pos'])) {
            $steps[] = $this->step(
                id: 'quick_create',
                route: $home,
                selector: '[data-tour="quick-create"]',
                title: 'appTour.steps.quick_create.title',
                description: 'appTour.steps.quick_create.description',
            );
        }

        if ($route = $this->firstRoute($permissions, [
            'access_pos' => '/pos',
            'view_customer' => '/customers',
            'view_product' => '/products',
        ])) {
            $steps[] = $this->moduleStep('sales', $route, 'appTour.steps.sales');
        }

        if ($route = $this->firstRoute($permissions, [
            'view_invoice' => '/invoices',
            'view_quote' => '/quotes',
            'view_delivery_note' => '/delivery-notes',
            'view_credit_note' => '/credit-notes',
        ])) {
            $steps[] = $this->moduleStep('invoicing', $route, 'appTour.steps.invoicing');
        }

        if ($route = $this->firstRoute($permissions, [
            'view_supplier' => '/suppliers',
            'view_purchase_order' => '/purchase-orders',
        ])) {
            $steps[] = $this->moduleStep('purchasing', $route, 'appTour.steps.purchasing');
        }

        if ($route = $this->firstRoute($permissions, [
            'view_stock' => '/stock',
            'view_warehouse' => '/warehouses',
            'view_category' => '/categories',
            'view_unit' => '/units',
        ])) {
            $steps[] = $this->moduleStep('inventory', $route, 'appTour.steps.inventory');
        }

        if ($route = $this->firstRoute($permissions, [
            'view_payment' => '/payments',
            'view_expenses' => '/expenses',
            'view_capital' => '/capital',
        ])) {
            $steps[] = $this->moduleStep('finance', $route, 'appTour.steps.finance');
        }

        if ($this->can($permissions, 'view_expenses')) {
            $steps[] = $this->moduleStep('payroll', '/payroll', 'appTour.steps.payroll');
        }

        if ($this->can($permissions, 'view_dashboard') && $this->can($permissions, 'view_profits')) {
            $steps[] = $this->step(
                id: 'reports',
                route: '/dashboard',
                selector: '[data-tour="dashboard-reports"]',
                title: 'appTour.steps.reports.title',
                description: 'appTour.steps.reports.description',
            );
        }

        if ($this->can($permissions, 'manage_users')) {
            $steps[] = $this->moduleStep('team', '/users', 'appTour.steps.team');
        }

        if ($this->can($permissions, 'view_activity_logs')) {
            $steps[] = $this->moduleStep('activity', '/activity-logs', 'appTour.steps.activity');
        }

        if ($this->can($permissions, 'manage_settings')) {
            $steps[] = $this->moduleStep('settings', '/settings', 'appTour.steps.settings');
        }

        $steps[] = $this->step(
            id: 'help',
            route: null,
            selector: '[data-tour="help"]',
            title: 'appTour.steps.help.title',
            description: 'appTour.steps.help.description',
        );
        $steps[] = $this->step(
            id: 'profile',
            route: null,
            selector: '[data-tour="profile"]',
            title: 'appTour.steps.profile.title',
            description: 'appTour.steps.profile.description',
        );

        return array_values(array_filter($steps));
    }

    /**
     * Dedicated tour for the global super administrator console.
     *
     * @return array<int, array<string, mixed>>
     */
    private function superAdminSteps(string $profile): array
    {
        return [
            $this->introStep($profile),
            $this->step(
                id: 'admin_dashboard',
                route: '/admin/dashboard',
                selector: '[data-tour="admin-dashboard"]',
                title: 'appTour.steps.admin_dashboard.title',
                description: 'appTour.steps.admin_dashboard.description',
            ),
            $this->step(
                id: 'admin_navigation',
                route: '/admin/dashboard',
                selector: '[data-tour="admin-navigation"]',
                title: 'appTour.steps.admin_navigation.title',
                description: 'appTour.steps.admin_navigation.description',
                focusSidebar: true,
            ),
            $this->step(
                id: 'admin_companies',
                route: '/admin/companies',
                selector: '[data-tour="admin-navigation"]',
                title: 'appTour.steps.admin_companies.title',
                description: 'appTour.steps.admin_companies.description',
                focusSidebar: true,
            ),
            $this->step(
                id: 'admin_subscriptions',
                route: '/admin/subscriptions',
                selector: '[data-tour="admin-navigation"]',
                title: 'appTour.steps.admin_subscriptions.title',
                description: 'appTour.steps.admin_subscriptions.description',
                focusSidebar: true,
            ),
            $this->step(
                id: 'admin_security',
                route: '/admin/login-logs',
                selector: '[data-tour="admin-navigation"]',
                title: 'appTour.steps.admin_security.title',
                description: 'appTour.steps.admin_security.description',
                focusSidebar: true,
            ),
            $this->step(
                id: 'admin_users',
                route: '/admin/users',
                selector: '[data-tour="admin-navigation"]',
                title: 'appTour.steps.admin_users.title',
                description: 'appTour.steps.admin_users.description',
                focusSidebar: true,
            ),
            $this->step(
                id: 'profile',
                route: null,
                selector: '[data-tour="profile"]',
                title: 'appTour.steps.profile.title',
                description: 'appTour.steps.profile.description',
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function introStep(string $profile): array
    {
        return $this->step(
            id: 'welcome',
            route: null,
            selector: null,
            title: 'appTour.profiles.'.$profile.'.welcome_title',
            description: 'appTour.profiles.'.$profile.'.welcome_description',
            type: 'intro',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function moduleStep(string $id, string $route, string $translationPrefix): array
    {
        return $this->step(
            id: $id,
            route: $route,
            selector: '[data-tour="page-header"]',
            title: $translationPrefix.'.title',
            description: $translationPrefix.'.description',
        );
    }

    /**
     * @param array<int, string> $permissions
     */
    private function can(array $permissions, string $permission): bool
    {
        return in_array($permission, $permissions, true);
    }

    /**
     * @param array<int, string> $permissions
     * @param array<int, string> $candidates
     */
    private function canAny(array $permissions, array $candidates): bool
    {
        return count(array_intersect($candidates, $permissions)) > 0;
    }

    /**
     * Return the first accessible route from an ordered permission/route map.
     *
     * @param array<int, string> $permissions
     * @param array<string, string> $candidates
     */
    private function firstRoute(array $permissions, array $candidates): ?string
    {
        foreach ($candidates as $permission => $route) {
            if ($this->can($permissions, $permission)) {
                return $route;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function step(
        string $id,
        ?string $route,
        ?string $selector,
        string $title,
        string $description,
        bool $focusSidebar = false,
        string $type = 'target',
    ): array {
        return [
            'id' => $id,
            'type' => $type,
            'route' => $route,
            'selector' => $selector,
            'title' => $title,
            'description' => $description,
            'focus_sidebar' => $focusSidebar,
        ];
    }
}
