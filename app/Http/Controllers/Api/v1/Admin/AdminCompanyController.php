<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminCompanyResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminCompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companies = Company::with(['plan'])
            ->withCount(['users'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->input('size'), function ($query, $size) {
                $query->where('size', $size);
            })
            ->when($request->input('plan_id'), function ($query, $planId) {
                $query->where('plan_id', $planId);
            })
            ->when($request->input('date_from'), function ($query, $date) {
                $query->whereDate('created_at', '>=', $date);
            })
            ->when($request->input('date_to'), function ($query, $date) {
                $query->whereDate('created_at', '<=', $date);
            })
            ->orderBy($request->input('sort', 'created_at'), $request->input('order', 'desc'))
            ->paginate($request->integer('per_page', 20));

        return AdminCompanyResource::collection($companies)->response();
    }

    public function show(Company $company): JsonResponse
    {
        $company->load(['plan', 'users']);
        $company->loadCount(['users', 'invoices', 'customers', 'products']);

        $lastLogin = $company->users()
            ->whereNotNull('last_login_at')
            ->max('last_login_at');

        $company->last_login = $lastLogin;
        $company->owner = $company->users->sortBy('id')->first();

        $invoicedVolume30d = (int) \App\Models\Invoice::where('company_id', $company->id)
            ->whereNull('deleted_at')
            ->where('status', '!=', \App\Enums\InvoiceStatus::Cancelled->value)
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('total_xof');

        $invoicesCount30d = (int) \App\Models\Invoice::where('company_id', $company->id)
            ->whereNull('deleted_at')
            ->where('status', '!=', \App\Enums\InvoiceStatus::Cancelled->value)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $company->invoiced_volume_30d = $invoicedVolume30d;
        $company->invoices_count_30d = $invoicesCount30d;

        $timeline = \Spatie\Activitylog\Models\Activity::with('causer')
            ->where('company_id', $company->id)
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'created_at' => $a->created_at,
                'description' => $a->description,
                'event' => $a->event,
                'log_name' => $a->log_name,
                'user' => $a->causer ? [
                    'name' => $a->causer->name,
                    'email' => $a->causer->email,
                ] : null,
            ]);

        $company->timeline = $timeline;

        return response()->json(new AdminCompanyResource($company), Response::HTTP_OK);
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'status' => ['sometimes', 'string', 'in:active,suspended,trial,disabled'],
            'size' => ['sometimes', 'nullable', 'string', 'max:50'],
            'industry' => ['sometimes', 'nullable', 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'address' => ['sometimes', 'nullable', 'string'],
            'plan_id' => ['sometimes', 'nullable', 'exists:plans,id'],
            'trial_ends_at' => ['sometimes', 'nullable', 'date'],
            'suspended_until' => ['sometimes', 'nullable', 'date'],
        ]);

        if (isset($validated['status'])) {
            $validated['suspended_at'] = $validated['status'] === 'suspended' ? now() : null;
        }

        $metadata = $company->metadata ?? [];
        foreach (['email', 'phone', 'address'] as $field) {
            if (array_key_exists($field, $validated)) {
                $metadata[$field] = $validated[$field];
                unset($validated[$field]);
            }
        }
        if (array_key_exists('suspended_until', $validated)) {
            if ($validated['suspended_until'] === null) {
                unset($metadata['suspended_until']);
            } else {
                $metadata['suspended_until'] = $validated['suspended_until'];
            }
            unset($validated['suspended_until']);
        }
        if ($metadata !== ($company->metadata ?? [])) {
            $validated['metadata'] = $metadata;
        }

        $company->update($validated);

        return response()->json(new AdminCompanyResource($company->fresh('plan')), Response::HTTP_OK);
    }

    public function suspend(Company $company): JsonResponse
    {
        $company->update([
            'status' => 'suspended',
            'suspended_at' => now(),
        ]);

        return response()->json([
            'message' => 'Entreprise suspendue.',
            'company' => new AdminCompanyResource($company->fresh()),
        ], Response::HTTP_OK);
    }

    public function activate(Company $company): JsonResponse
    {
        $company->update([
            'status' => 'active',
            'suspended_at' => null,
        ]);

        return response()->json([
            'message' => 'Entreprise activée.',
            'company' => new AdminCompanyResource($company->fresh()),
        ], Response::HTTP_OK);
    }

    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Company::count(),
            'active' => Company::where('status', 'active')->count(),
            'suspended' => Company::where('status', 'suspended')->count(),
            'trial' => Company::where('status', 'trial')->count(),
            'disabled' => Company::where('status', 'disabled')->count(),
            'by_size' => Company::whereNotNull('size')
                ->selectRaw('size, COUNT(*) as count')
                ->groupBy('size')
                ->pluck('count', 'size'),
            'by_industry' => Company::whereNotNull('industry')
                ->selectRaw('industry, COUNT(*) as count')
                ->groupBy('industry')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'industry'),
            'registrations_last_30_days' => Company::where('created_at', '>=', now()->subDays(30))->count(),
            'registrations_last_7_days' => Company::where('created_at', '>=', now()->subDays(7))->count(),
            'plans' => \App\Models\Plan::orderBy('sort')->get(['id', 'name', 'slug', 'price_xof', 'is_active']),
        ];

        return response()->json($stats, Response::HTTP_OK);
    }

    /**
     * Export companies matching the same filters as index().
     */
    public function export(Request $request)
    {
        $companies = Company::with(['plan'])
            ->withCount(['users'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->input('size'), function ($query, $size) {
                $query->where('size', $size);
            })
            ->orderBy('created_at', 'desc')
            ->limit(10000)
            ->get();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AdminCompaniesExport($companies),
            'entreprises-' . date('Y-m-d-His') . '.csv',
            \Maatwebsite\Excel\Excel::CSV
        );
    }
}
