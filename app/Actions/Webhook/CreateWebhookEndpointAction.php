<?php

declare(strict_types=1);

namespace App\Actions\Webhook;

use App\DTOs\WebhookEndpointDTO;
use App\Models\WebhookEndpoint;
use App\Support\TenantContext;

class CreateWebhookEndpointAction
{
    public function execute(WebhookEndpointDTO $dto): WebhookEndpoint
    {
        return WebhookEndpoint::create([
            'company_id' => TenantContext::getCompanyId(),
            'name' => $dto->name,
            'url' => $dto->url,
            'secret' => $dto->secret,
            'events' => $dto->events,
            'is_active' => $dto->is_active,
        ]);
    }
}
