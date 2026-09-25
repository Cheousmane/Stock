<?php

declare(strict_types=1);

namespace App\Services\PaymentGateways;

use App\Models\Company;
use App\Models\PaymentLink;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Facade;

class PaymentGatewayManager
{
    public function __construct(
        private readonly array $gateways = []
    ) {
        //
    }

    public static function make(): static
    {
        return new static();
    }

    public function registerGateway(string $name, callable $serviceFactory): static
    {
        $this->gateways[$name] = $serviceFactory;

        return $this;
    }

    public function getGatewayService(string $gatewayName): ?\App\Services\PaymentGateways\PaymentGatewayInterface
    {
        if (!isset($this->gateways[$gatewayName])) {
            return null;
        }

        $factory = $this->gateways[$gatewayName];
        $service = $factory();

        // Resolve dependencies via container if needed
        return $service;
    }

    public function createPaymentLink(PaymentLink $paymentLink, string $successUrl, string $errorUrl): ?string
    {
        $gateway = $paymentLink->gateway ?? 'wave';

        $service = $this->getGatewayService($gateway);

        if (!$service) {
            // Fallback to CinetPay if available
            $service = $this->getGatewayService('cinetpay');
        }

        if (!$service) {
            return null;
        }

        return $service->initiateCheckout($paymentLink->transactions->first() ?? null, $successUrl, $errorUrl)['checkout_url'] ?? $service->createPaymentLink($paymentLink->transactions->first() ?? null, $successUrl, $errorUrl);
    }

    public function handleWebhookNotification(string $gatewayName, string $payload): ?PaymentTransaction
    {
        $service = $this->getGatewayService($gatewayName);

        if (!$service) {
            return null;
        }

        $transaction = null;

        // Try to find transaction by external reference from webhook payload
        $reference = $this->extractReferenceFromPayload($payload, $service);

        if ($reference) {
            $transaction = PaymentTransaction::where('external_reference', $reference)
                ->where('company_id', TenantContext::getCompanyId())
                ->first();
        }

        if (!$transaction && $service instanceof \App\Services\PaymentGateways\CinetPayPaymentService) {
            // CinetPay specific: parse webhook notification
            $parsed = $service->parseWebhookNotification($payload);
            if ($parsed && $parsed['reference']) {
                $transaction = PaymentTransaction::where('external_reference', $parsed['reference'])
                    ->where('company_id', TenantContext::getCompanyId())
                    ->first();
            }
        }

        if ($transaction) {
            $service->updateTransactionStatus($transaction, $payload);
        }

        return $transaction;
    }

    protected function extractReferenceFromPayload(string $payload, $service): ?string
    {
        // Generic extraction, to be overridden per gateway if needed
        return null;
    }
}