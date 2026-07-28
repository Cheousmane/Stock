<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Actions\Webhook\CreateWebhookEndpointAction;
use App\Actions\Webhook\UpdateWebhookEndpointAction;
use App\DTOs\WebhookEndpointDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\WebhookEndpointRequest;
use App\Http\Resources\WebhookEndpointResource;
use App\Models\WebhookEndpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebhookEndpointController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WebhookEndpoint::class);
        $endpoints = WebhookEndpoint::orderBy('name')->get();
        return response()->json(WebhookEndpointResource::collection($endpoints), Response::HTTP_OK);
    }

    public function store(WebhookEndpointRequest $request, CreateWebhookEndpointAction $action): JsonResponse
    {
        $this->authorize('create', WebhookEndpoint::class);
        $dto = WebhookEndpointDTO::fromArray($request->validated());
        $endpoint = $action->execute($dto);
        return response()->json(new WebhookEndpointResource($endpoint), Response::HTTP_CREATED);
    }

    public function show(WebhookEndpoint $webhookEndpoint): JsonResponse
    {
        $this->authorize('view', $webhookEndpoint);
        return response()->json(new WebhookEndpointResource($webhookEndpoint), Response::HTTP_OK);
    }

    public function update(WebhookEndpointRequest $request, WebhookEndpoint $webhookEndpoint, UpdateWebhookEndpointAction $action): JsonResponse
    {
        $this->authorize('update', $webhookEndpoint);
        $dto = WebhookEndpointDTO::fromArray($request->validated());
        $endpoint = $action->execute($webhookEndpoint, $dto);
        return response()->json(new WebhookEndpointResource($endpoint), Response::HTTP_OK);
    }

    public function destroy(WebhookEndpoint $webhookEndpoint): JsonResponse
    {
        $this->authorize('delete', $webhookEndpoint);
        $webhookEndpoint->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function events(): JsonResponse
    {
        return response()->json([
            'data' => (new WebhookEndpointRequest)->availableEvents(),
        ], Response::HTTP_OK);
    }
}
