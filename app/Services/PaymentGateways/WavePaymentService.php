<?php

declare(strict_types=1);

namespace App\Services\PaymentGateways;

use App\Models\Company;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WavePaymentService
{
    private string $baseUrl = 'https://api.wave.com/v1';

    public function getApiKey(?Company $company = null): ?string
    {
        if ($company) {
            $key = Setting::where('company_id', $company->id)->where('key', 'wave_api_key')->value('value');
            if ($key) return $key;
        }
        return config('services.wave.api_key');
    }

    public function initiateCheckout(PaymentTransaction $transaction, string $successUrl, string $errorUrl, ?Company $company = null): array
    {
        $apiKey = $this->getApiKey($company);
        if (!$apiKey) {
            // Simulated / Mock mode for test & development if no API key configured
            return [
                'success' => true,
                'checkout_url' => url('/pay/mock-gateway/wave?ref=' . $transaction->transaction_reference),
                'wave_launch_url' => url('/pay/mock-gateway/wave?ref=' . $transaction->transaction_reference),
                'id' => 'wave_sim_' . $transaction->transaction_reference,
            ];
        }

        try {
            $response = Http::withToken($apiKey)
                ->post("{$this->baseUrl}/checkout/sessions", [
                    'amount' => (string) $transaction->amount_xof,
                    'currency' => 'XOF',
                    'error_url' => $errorUrl,
                    'success_url' => $successUrl,
                    'client_reference' => $transaction->transaction_reference,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $transaction->update([
                    'external_reference' => $data['id'] ?? null,
                    'response_payload' => $data,
                ]);

                return [
                    'success' => true,
                    'checkout_url' => $data['wave_launch_url'] ?? $data['checkout_url'] ?? null,
                    'wave_launch_url' => $data['wave_launch_url'] ?? null,
                    'id' => $data['id'] ?? null,
                ];
            }

            $errorMessage = $response->json('message') ?? 'Erreur Wave API: ' . $response->status();
            $transaction->update(['error_message' => $errorMessage]);

            return ['success' => false, 'error' => $errorMessage];
        } catch (\Throwable $e) {
            Log::error('Wave checkout exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function verifyWebhookSignature(string $payload, string $signature, ?string $webhookSecret = null): bool
    {
        $secret = $webhookSecret ?? config('services.wave.webhook_secret');
        if (!$secret) {
            return true; // if secret is not configured yet in dev
        }
        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }
}
