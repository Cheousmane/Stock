<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = Activity::query()
            ->with('causer')
            ->orderBy('created_at', 'desc')
            ->when($search = $request->input('search'), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                      ->orWhere('log_name', 'like', "%{$search}%")
                      ->orWhere('event', 'like', "%{$search}%");
                });
            })
            ->paginate($request->integer('per_page', 20));

        return response()->json(ActivityLogResource::collection($logs), Response::HTTP_OK);
    }
}
