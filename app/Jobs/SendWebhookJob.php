<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WebhookEndpoint;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    private const TIMEOUT = 10;

    public function __construct(
        private readonly WebhookEndpoint $endpoint,
        private readonly string $event,
        private readonly array $payload,
    ) {}

    public function handle(): void
    {
        $payload = [
            'event' => $this->event,
            'data' => $this->payload,
            'sent_at' => now()->toIso8601String(),
        ];

        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'FacturationStock-Webhook/1.0',
        ];

        if ($this->endpoint->secret) {
            $signature = hash_hmac('sha256', json_encode($payload), $this->endpoint->secret);
            $headers['X-Webhook-Signature'] = $signature;
        }

        Http::timeout(self::TIMEOUT)
            ->withHeaders($headers)
            ->post($this->endpoint->url, $payload);
    }
}
