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
        ];

        return response()->json($stats, Response::HTTP_OK);
    }
}
