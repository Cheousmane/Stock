<?php

declare(strict_types=1);

namespace App\Services\PaymentGateways;

use App\Models\Company;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CinetPayPaymentService
{
    private string $apiUrl;
    private string $merchantId;
    private string $accessCode;
    private bool $sandbox;

    public function __construct()
    {
        $merchantId = config('services.cinetpay.merchant_id') ?? null;
        $accessCode = config('services.cinetpay.access_code') ?? null;
        $sandbox = config('services.cinetpay.sandbox') ?? env('CINETPAY_SANDBOX', false);

        if ($merchantId && $accessCode) {
            $this->merchantId = $merchantId;
            $this->accessCode = $accessCode;
            $this->sandbox = $sandbox;
            $this->apiUrl = $sandbox ? 'https://api.cinetpay.com/v2/sandbox' : 'https://api.cinetpay.com/v2';
        } else {
            // Si les clés ne sont pas configurées, on désactive le service
            $this->apiUrl = '';
        }
    }

    public function createPaymentLink(PaymentTransaction $transaction, string $successUrl, string $errorUrl): ?string
    {
        if (empty($this->apiUrl)) {
            return null;
        }

        try {
            $amount = (string) $transaction->amount_xof;
            $reference = $transaction->transaction_reference ?? 'INV-' . date('YmdHis');

            $data = [
                'merchant_id' => $this->merchantId,
                'access_code' => $this->accessCode,
                'amount' => $amount,
                'currency' => 'XOF',
                'return_url' => $successUrl,
                'cancel_url' => $errorUrl,
                'description' => 'Paiement facture #' . ($transaction->invoice?->number ?? ''),
                'customer' => [
                    'first_name' => $transaction->customer_name ?? '',
                    'last_name' => '',
                    'email' => $transaction->customer_email ?? '',
                    'phone' => $transaction->customer_phone ?? '',
                ],
                'reference' => $reference,
            ];

            $response = Http::post("{$this->apiUrl}/paiement", $data);

            if ($response->successful()) {
                $result = $response->json();
                $transaction->update([
                    'external_reference' => $result['reference'] ?? $reference,
                    'response_payload' => $result,
                ]);

                // CinetPay returns a payment page URL
                return $result['pay_display_url'] ?? null;
            }

            $errorMessage = $response->json('message') ?? 'Erreur CinetPay API: ' . $response->status();
            $transaction->update(['error_message' => $errorMessage]);

            Log::error('CinetPay payment error', ['response' => $response->json()]);

            return null;
        } catch (\Throwable $e) {
            Log::error('CinetPay exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function verifyWebhookSignature(string $body, string $signature): bool
    {
        // CinetPay sends a HMAC-SHA256 signature in the header X-Signature
        // We validate it using the access code / merchant secret
        if (empty($this->accessCode)) {
            return true; // Dev mode fallback
        }

        $expected = hash_hmac('sha256', $body, $this->accessCode);
        return hash_equals($expected, $signature);
    }

    public function parseWebhookNotification(string $body): ?array
    {
        // Parse CinetPay webhook body to extract status and reference
        // Typically contains: reference, status, amount, etc.
        $data = json_decode($body, true);

        if (json_last_error() === JSON_ERROR_NONE && $data) {
            return [
                'reference' => $data['reference'] ?? null,
                'status' => $data['status'] ?? null,
                'amount' => $data['amount'] ?? null,
                'currency' => $data['currency'] ?? null,
                'timestamp' => $data['timestamp'] ?? null,
            ];
        }

        return null;
    }
}