<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendWebhookJob;
use App\Models\WebhookEndpoint;
use App\Support\TenantContext;

class WebhookDispatcherService
{
    public function dispatch(string $event, array $payload): void
    {
        $companyId = TenantContext::getCompanyId();

        $endpoints = WebhookEndpoint::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        foreach ($endpoints as $endpoint) {
            if (in_array($event, $endpoint->events ?? [], true)) {
                SendWebhookJob::dispatch($endpoint, $event, $payload);
            }
        }
    }
}
