<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\Warehouse;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionQuota
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = TenantContext::get();
        $companyId = $company->id;

        $plan = Cache::remember("plan.{$companyId}", 3600, function () use ($company) {
            if ($company->plan_id) {
                return Plan::find($company->plan_id);
            }

            $subscription = Subscription::where('company_id', $company->id)
                ->where('stripe_status', 'active')
                ->latest()
                ->first();

            if (!$subscription) {
                return Plan::where('slug', 'free')->first();
            }

            $plan = Plan::where('stripe_price_id', $subscription->stripe_price)->first();

            if ($plan && $plan->slug !== 'free' && !$subscription) {
                abort(Response::HTTP_PAYMENT_REQUIRED, 'Active subscription required.');
            }

            return $plan;
        });

        if ($request->isMethod('POST')) {
            $this->enforceQuotas($request, $plan, $companyId);
        }

        return $next($request);
    }

    private function enforceQuotas(Request $request, ?Plan $plan, int $companyId): void
    {
        if (!$plan || !is_array($plan->quotas)) {
            return;
        }

        $routeName = $request->route() ? $request->route()->getName() : '';

        $counts = [];

        if (str_starts_with($routeName, 'api.v1.users.store') || str_starts_with($routeName, 'users.store')) {
            $maxUsers = $plan->quotas['max_users'] ?? -1;
            if ($maxUsers !== -1) {
                $counts['users'] ??= Cache::remember("quota_count.users.{$companyId}", 60, fn () => User::where('company_id', $companyId)->count());
                if ($counts['users'] >= $maxUsers) {
                    abort(Response::HTTP_FORBIDDEN, 'User quota exceeded for your plan.');
                }
            }
        }

        if (str_starts_with($routeName, 'api.v1.products.store') || str_starts_with($routeName, 'products.store')) {
            $maxProducts = $plan->quotas['max_products'] ?? -1;
            if ($maxProducts !== -1) {
                $counts['products'] ??= Cache::remember("quota_count.products.{$companyId}", 60, fn () => Product::where('company_id', $companyId)->count());
                if ($counts['products'] >= $maxProducts) {
                    abort(Response::HTTP_FORBIDDEN, 'Product quota exceeded for your plan.');
                }
            }
        }

        if (str_starts_with($routeName, 'api.v1.invoices.store') || str_starts_with($routeName, 'invoices.store')) {
            $maxInvoices = $plan->quotas['max_invoices'] ?? -1;
            if ($maxInvoices !== -1) {
                $counts['invoices'] ??= Cache::remember("quota_count.invoices.{$companyId}", 60, fn () => Invoice::where('company_id', $companyId)->count());
                if ($counts['invoices'] >= $maxInvoices) {
                    abort(Response::HTTP_FORBIDDEN, 'Invoice quota exceeded for your plan.');
                }
            }
        }

        if (str_starts_with($routeName, 'api.v1.warehouses.store') || str_starts_with($routeName, 'warehouses.store')) {
            $maxWarehouses = $plan->quotas['max_warehouses'] ?? -1;
            if ($maxWarehouses !== -1) {
                $counts['warehouses'] ??= Cache::remember("quota_count.warehouses.{$companyId}", 60, fn () => Warehouse::where('company_id', $companyId)->count());
                if ($counts['warehouses'] >= $maxWarehouses) {
                    abort(Response::HTTP_FORBIDDEN, 'Warehouse quota exceeded for your plan.');
                }
            }
        }
    }
}
