<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function show(): JsonResponse
    {
        $company = TenantContext::get();
        return response()->json($company);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorize('manage_settings');
        $company = TenantContext::get();
        $validated = $request->validate([
            'company_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email|max:255',
            'phone' => 'sometimes|nullable|string|max:255',
            'address' => 'sometimes|nullable|string|max:255',
            'currency' => 'sometimes|nullable|string|max:10',
        ]);

        $data = [];
        if (isset($validated['company_name'])) {
            $data['name'] = $validated['company_name'];
        }

        $metadata = $company->metadata ?? [];
        foreach (['email', 'phone', 'address', 'currency'] as $field) {
            if (array_key_exists($field, $validated)) {
                $metadata[$field] = $validated[$field];
            }
        }
        $data['metadata'] = $metadata;

        $company->update($data);
        return response()->json($company);
    }

    public function switchPlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_slug' => ['required', 'string', 'exists:plans,slug'],
        ]);

        $plan = Plan::where('slug', $validated['plan_slug'])->firstOrFail();
        $company = TenantContext::get();
        $company->update(['plan_id' => $plan->id]);

        return response()->json([
            'message' => 'Plan switched to ' . $plan->name,
            'plan' => $plan,
        ]);
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $this->authorize('manage_settings');
        $request->validate(['logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048']);
        $company = TenantContext::get();
        $path = $request->file('logo')->store('logos', 'public');
        $metadata = $company->metadata ?? [];
        $metadata['logo'] = $path;
        $company->update(['metadata' => $metadata]);
        return response()->json(['logo_url' => Storage::url($path)]);
    }

    public function deleteLogo(): JsonResponse
    {
        $this->authorize('manage_settings');
        $company = TenantContext::get();
        $metadata = $company->metadata ?? [];

        if (isset($metadata['logo'])) {
            Storage::disk('public')->delete($metadata['logo']);
            unset($metadata['logo']);
            $company->update(['metadata' => $metadata]);
        }

        return response()->json(['message' => 'Logo deleted']);
    }
}
