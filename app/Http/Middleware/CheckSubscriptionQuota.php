<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\QuotaService;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applique les quotas du plan effectif à toutes les créations de ressources
 * soumises à limite. Les données gelées comptent dans l'usage.
 */
class CheckSubscriptionQuota
{
    /**
     * Route de création => ressource soumise au quota.
     *
     * @var array<string, string>
     */
    private const ROUTE_RESOURCES = [
        'users.store' => 'users',
        'products.store' => 'products',
        'products.duplicate' => 'products',
        'invoices.store' => 'invoices',
        'quotes.convert-to-invoice' => 'invoices',
        'warehouses.store' => 'warehouses',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST')) {
            return $next($request);
        }

        // Les super admins ne sont pas soumis aux quotas.
        if ($request->user()?->is_super_admin) {
            return $next($request);
        }

        $company = TenantContext::get();

        if ($company === null) {
            return $next($request);
        }

        // Essai actif => accès complet, sans quotas.
        if ($company->hasActiveTrial()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';
        $resource = null;

        foreach (self::ROUTE_RESOURCES as $suffix => $mappedResource) {
            if ($routeName === $suffix || str_ends_with($routeName, '.'.$suffix)) {
                $resource = $mappedResource;
                break;
            }
        }

        if ($resource !== null) {
            app(QuotaService::class)->ensureCanCreate($company, $resource);
        }

        return $next($request);
    }
}
