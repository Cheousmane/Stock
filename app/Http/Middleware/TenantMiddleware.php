<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Company;
use App\Services\SubscriptionService;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            if (!$user->is_active || $user->frozen_at !== null || ($user->company && $user->company->status === 'suspended')) {
                abort(Response::HTTP_FORBIDDEN, $user->frozen_at !== null
                    ? 'Votre compte utilisateur est gelé : les quotas de votre plan sont dépassés. Contactez votre administrateur.'
                    : 'Votre compte a été suspendu.');
            }

            $company = $user->company;

            // Application immédiate des fins d'essai/abonnement sans attendre
            // le planificateur (qui reste la garantie de fond).
            if ($company && ($company->status === 'trial' || ($company->status === 'active' && $company->plan_id !== null && $company->plan && ! $company->plan->isFree()))) {
                $company = app(SubscriptionService::class)->ensureCompanyIsUpToDate($company);
                $user->setRelation('company', $company);
            }

            TenantContext::set($company);
            app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);

            if (!$user->last_seen_at || $user->last_seen_at->diffInMinutes(now()) >= 5) {
                $user->withoutEvents(fn () => $user->updateQuietly(['last_seen_at' => now()]));
            }

            return $next($request);
        }

        $slug = $this->resolveTenantSlug($request);

        if (!$slug) {
            abort(Response::HTTP_NOT_FOUND, 'Tenant not specified.');
        }

        $company = Company::where('slug', $slug)->first();

        if (!$company) {
            abort(Response::HTTP_NOT_FOUND, 'Tenant not found.');
        }

        TenantContext::set($company);
        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);

        return $next($request);
    }

    private function resolveTenantSlug(Request $request): ?string
    {
        if ($request->headers->has('X-Tenant-Slug')) {
            return $request->header('X-Tenant-Slug');
        }

        $host = $request->getHost();
        $baseHost = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';

        if ($host !== $baseHost && str_ends_with($host, '.' . $baseHost)) {
            return str_replace('.' . $baseHost, '', $host);
        }

        if ($request->route() && $request->route()->hasParameter('company_slug')) {
            return $request->route()->parameter('company_slug');
        }

        return null;
    }
}
