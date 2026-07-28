<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Event::class);
        $events = Event::orderByDesc('start_date')
            ->when($search = $request->input('search'), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('type', 'like', "%{$search}%");
                });
            })
            ->paginate($request->integer('per_page', 20));

        return response()->json(EventResource::collection($events), Response::HTTP_OK);
    }

    public function show(Event $event): JsonResponse
    {
        $this->authorize('view', $event);
        return response()->json(new EventResource($event), Response::HTTP_OK);
    }

    public function store(EventRequest $request): JsonResponse
    {
        $this->authorize('create', Event::class);
        $event = Event::create([
            'company_id' => TenantContext::getCompanyId(),
            'title' => $request->input('title'),
            'type' => $request->input('type'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'description' => $request->input('description'),
            'created_by' => $request->user()?->id,
        ]);

        return response()->json(new EventResource($event), Response::HTTP_CREATED);
    }

    public function update(EventRequest $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);
        $event->update($request->validated());
        return response()->json(new EventResource($event), Response::HTTP_OK);
    }

    public function destroy(Event $event): JsonResponse
    {
        $this->authorize('delete', $event);
        $event->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
