<?php

declare(strict_types=1);

namespace App\Services\PaymentGateways;

use App\Models\PaymentTransaction;

interface PaymentGatewayInterface
{
    public function initiateCheckout(PaymentTransaction $transaction, string $successUrl, string $errorUrl, ?Company $company = null): array;

    public function verifyWebhookSignature(string $payload, string $signature, ?string $webhookSecret = null): bool;

    public function updateTransactionStatus(PaymentTransaction $transaction, string $webhookPayload): void;

    public function parseWebhookNotification(string $body): ?array;
}