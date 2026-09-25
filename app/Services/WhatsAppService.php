<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Quote;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

final class WhatsAppService
{
    private string $apiBaseUrl;
    private string $phoneNumberId;
    private string $accessToken;

    public function __construct()
    {
        $this->apiBaseUrl = config('services.whatsapp.api_base_url') ?? 'https://graph.facebook.com/v20.0';
        $this->phoneNumberId = config('services.whatsapp.phone_number_id') ?? '';
        $this->accessToken = config('services.whatsapp.access_token') ?? '';
    }

    public function sendMessage(string $toPhone, string $templateName, array $templateData, ?Company $company = null): array
    {
        if (empty($this->accessToken) || empty($this->phoneNumberId)) {
            Log::warning('WhatsApp service not configured - skipping message send');
            return ['success' => false, 'error' => 'WhatsApp API not configured'];
        }

        try {
            $payload = [
                'to' => $toPhone,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => ['code' => 'fr'],
                ],
            ];

            // Merge template data
            if (!empty($templateData)) {
                $payload['template']['components'] = [];
                foreach ($templateData as $key => $value) {
                    $payload['template']['components'][] = [
                        'type' => 'body',
                        'parameters' => [[
                            'type' => 'text',
                            'text' => (string) $value,
                        ]],
                    ];
                }
            }

            $response = Http::withToken($this->accessToken)
                ->post("{$this->apiBaseUrl}/{$this->phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json('messages.0.id') ?? null,
                    'status' => $response->json('messages.0.status') ?? null,
                ];
            }

            $errorMessage = $response->json('error')?.message ?? 'Erreur WhatsApp API: ' . $response->status();
            Log::error('WhatsApp send error', ['response' => $response->json()]);

            return ['success' => false, 'error' => $errorMessage];
        } catch (\Throwable $e) {
            Log::error('WhatsApp exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendInvoicePaidNotification(Invoice $invoice, ?Company $company = null): array
    {
        $customer = $invoice->customer;
        if (!$customer || empty($customer->phone)) {
            return ['success' => false, 'error' => 'Customer phone number not available'];
        }

        $templateData = [
            'customer_name' => $customer->name ?? '',
            'invoice_number' => $invoice->number ?? '',
            'amount' => \App\Support\Money::format($invoice->total_xof, $invoice->company?->metadata['currency'] ?? 'XOF'),
            'payment_date' => now()->format('d/m/Y'),
        ];

        return $this->sendMessage(
            $customer->phone,
            'invoice_paid', // Template name
            $templateData,
            $company
        );
    }

    public function sendInvoiceOverdueReminder(Invoice $invoice, ?Company $company = null): array
    {
        $customer = $invoice->customer;
        if (!$customer || empty($customer->phone)) {
            return ['success' => false, 'error' => 'Customer phone number not available'];
        }

        $templateData = [
            'customer_name' => $customer->name ?? '',
            'invoice_number' => $invoice->number ?? '',
            'amount' => \App\Support\Money::format($invoice->balance_due_xof, $invoice->company?->metadata['currency'] ?? 'XOF'),
            'due_date' => $invoice->due_date?->format('d/m/Y') ?? '',
        ];

        return $this->sendMessage(
            $customer->phone,
            'invoice_overdue_reminder', // Template name
            $templateData,
            $company
        );
    }

    public function sendQuoteAcceptedNotification(Quote $quote, ?Company $company = null): array
    {
        $customer = $quote->customer;
        if (!$customer || empty($customer->phone)) {
            return ['success' => false, 'error' => 'Customer phone number not available'];
        }

        $templateData = [
            'customer_name' => $customer->name ?? '',
            'quote_number' => $quote->number ?? '',
            'amount' => \App\Support\Money::format($quote->total_xof, $quote->company?->metadata['currency'] ?? 'XOF'),
        ];

        return $this->sendMessage(
            $customer->phone,
            'quote_accepted',
            $templateData,
            $company
        );
    }
}