<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\CapitalService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CapitalController extends Controller
{
    public function __construct(
        private readonly CapitalService $capitalService,
    ) {}

    public function show(): JsonResponse
    {
        Gate::authorize('view_capital');

        $company = TenantContext::get();
        if (!$company) {
            return response()->json(['message' => 'Aucune société trouvée.'], 404);
        }

        return response()->json([
            'data' => $this->capitalService->calculate($company),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        Gate::authorize('view_capital');

        $validated = $request->validate([
            'initial_capital' => 'required|integer|min:0',
        ]);

        $company = TenantContext::get();
        if (!$company) {
            return response()->json(['message' => 'Aucune société trouvée.'], 404);
        }

        $company->update([
            'manual_capital' => $validated['initial_capital'],
            'capital_updated_at' => now(),
        ]);

        return response()->json([
            'data' => $this->capitalService->calculate($company->fresh()),
            'message' => 'Capital mis à jour avec succès.',
        ]);
    }
}
