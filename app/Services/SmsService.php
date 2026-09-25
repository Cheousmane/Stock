<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Quote;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class SmsService
{
    private string $baseUrl;
    private string $apiKey;
    private string $senderId;

    public function __construct()
    {
        $this->baseUrl = config('services.sms.base_url') ?? 'https://api.termii.com/sms';
        $this->apiKey = config('services.sms.api_key') ?? env('SMS_API_KEY');
        $this->senderId = config('services.sms.sender_id') ?? 'FACTURATION';
    }

    public function send(string $toPhone, string $message, ?Company $company = null): array
    {
        if (empty($this->apiKey)) {
            Log::warning('SMS service not configured - skipping SMS send');
            return ['success' => false, 'error' => 'SMS API not configured'];
        }

        try {
            $payload = [
                'api_key' => $this->apiKey,
                'to' => $toPhone,
                'from' => $this->senderId,
                'body' => $message,
                'channel' => 'transactional',
                'type' => 'plain',
            ];

            $response = Http::post($this->baseUrl, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json('message_id') ?? null,
                    'status' => $response->json('status') ?? null,
                ];
            }

            $errorMessage = $response->json('message') ?? 'Erreur SMS API: ' . $response->status();
            Log::error('SMS send error', ['response' => $response->json()]);

            return ['success' => false, 'error' => $errorMessage];
        } catch (\Throwable $e) {
            Log::error('SMS exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendInvoicePaidNotification(Invoice $invoice, ?Company $company = null): array
    {
        $customer = $invoice->customer;
        if (!$customer || empty($customer->phone)) {
            return ['success' => false, 'error' => 'Customer phone number not available'];
        }

        $amount = \App\Support\Money::format($invoice->total_xof, $invoice->company?->metadata['currency'] ?? 'XOF');

        $message = sprintf(
            "Bonjour %s, votre facture %s d'un montant de %s XOF a été marquée comme payée le %s. Merci !",
            $customer->name ?? 'Client',
            $invoice->number ?? 'INV-',
            $amount,
            now()->format('d/m/Y')
        );

        return $this->send($customer->phone, $message);
    }

    public function sendInvoiceOverdueReminder(Invoice $invoice, ?Company $company = null): array
    {
        $customer = $invoice->customer;
        if (!$customer || empty($customer->phone)) {
            return ['success' => false, 'error' => 'Customer phone number not available'];
        }

        $amount = \App\Support\Money::format($invoice->balance_due_xof, $invoice->company?->metadata['currency'] ?? 'XOF');
        $dueDate = $invoice->due_date?->format('d/m/Y') ?? 'date';

        $message = sprintf(
            "Bonjour %s, votre facture %s d'un montant de %s XOF est en retard (due depuis le %s). Veuillez régler rapidement.",
            $customer->name ?? 'Client',
            $invoice->number ?? 'INV-',
            $amount,
            $dueDate
        );

        return $this->send($customer->phone, $message);
    }

    public function sendQuoteAcceptedNotification(Quote $quote, ?Company $company = null): array
    {
        $customer = $quote->customer;
        if (!$customer || empty($customer->phone)) {
            return ['success' => false, 'error' => 'Customer phone number not available'];
        }

        $amount = \App\Support\Money::format($quote->total_xof, $quote->company?->metadata['currency'] ?? 'XOF');

        $message = sprintf(
            "Bonjour %s, votre devis %s d'un montant de %s XOF a été accepté. Merci !",
            $customer->name ?? 'Client',
            $quote->number ?? 'DEV-',
            $amount
        );

        return $this->send($customer->phone, $message);
    }
}