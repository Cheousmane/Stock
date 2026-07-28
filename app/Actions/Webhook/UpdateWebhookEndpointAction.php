<?php

declare(strict_types=1);

namespace App\Actions\Webhook;

use App\DTOs\WebhookEndpointDTO;
use App\Models\WebhookEndpoint;

class UpdateWebhookEndpointAction
{
    public function execute(WebhookEndpoint $endpoint, WebhookEndpointDTO $dto): WebhookEndpoint
    {
        $endpoint->update([
            'name' => $dto->name,
            'url' => $dto->url,
            'secret' => $dto->secret,
            'events' => $dto->events,
            'is_active' => $dto->is_active,
        ]);

        return $endpoint->fresh();
    }
}
