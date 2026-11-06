<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\TourService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TourController extends Controller
{
    /**
     * Return the tour rebuilt from the authenticated user's current access.
     */
    public function show(Request $request, TourService $tour): JsonResponse
    {
        return response()->json($tour->payload($request->user()), Response::HTTP_OK);
    }

    /**
     * Persist completion (or skip) for the current access signature.
     */
    public function complete(Request $request, TourService $tour): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'tour_completed_at' => now(),
            'tour_completed_hash' => $tour->signatureFor($user),
        ])->save();

        return response()->json([
            'completed' => true,
            'completed_at' => $user->tour_completed_at?->toIso8601String(),
        ], Response::HTTP_OK);
    }

    /**
     * Clear completion so the next tour request starts from the beginning.
     */
    public function restart(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'tour_completed_at' => null,
            'tour_completed_hash' => null,
        ])->save();

        return response()->json([
            'completed' => false,
            'should_start' => true,
        ], Response::HTTP_OK);
    }
}
