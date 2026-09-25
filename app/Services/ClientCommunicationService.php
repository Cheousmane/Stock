<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\Quote;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

final class ClientCommunicationService
{
    public function sendInvoicePaid(Invoice $invoice): void
    {
        $company = $invoice->company;

        // Try WhatsApp first
        $whatsapp = App::make(WhatsAppService::class)->sendInvoicePaidNotification($invoice, $company);
        if ($whatsapp['success']) {
            Log::info("Invoice paid notification sent via WhatsApp for invoice #{$invoice->number}");
            return;
        }

        // Fall back to SMS
        $sms = App::make(SmsService::class)->sendInvoicePaidNotification($invoice, $company);
        if ($sms['success']) {
            Log::info("Invoice paid notification sent via SMS for invoice #{$invoice->number}");
            return;
        }

        Log::error("Failed to send invoice paid notification for invoice #{$invoice->number}", [
            'whatsapp_error' => $whatsapp['error'] ?? 'none',
            'sms_error' => $sms['error'] ?? 'none',
        ]);
    }

    public function sendInvoiceOverdueReminder(Invoice $invoice): void
    {
        $company = $invoice->company;

        // Try WhatsApp first
        $whatsapp = App::make(WhatsAppService::class)->sendInvoiceOverdueReminder($invoice, $company);
        if ($whatsapp['success']) {
            return;
        }

        // Fall back to SMS
        $sms = App::make(SmsService::class)->sendInvoiceOverdueReminder($invoice, $company);
        if ($sms['success']) {
            return;
        }

        Log::error("Failed to send invoice overdue reminder for invoice #{$invoice->number}", [
            'whatsapp_error' => $whatsapp['error'] ?? 'none',
            'sms_error' => $sms['error'] ?? 'none',
        ]);
    }

    public function sendQuoteAccepted(Quote $quote): void
    {
        $company = $quote->company;

        // Try WhatsApp first
        $whatsapp = App::make(WhatsAppService::class)->sendQuoteAcceptedNotification($quote, $company);
        if ($whatsapp['success']) {
            return;
        }

        // Fall back to SMS
        $sms = App::make(SmsService::class)->sendQuoteAcceptedNotification($quote, $company);
        if ($sms['success']) {
            return;
        }

        Log::error("Failed to send quote accepted notification for quote #{$quote->number}", [
            'whatsapp_error' => $whatsapp['error'] ?? 'none',
            'sms_error' => $sms['error'] ?? 'none',
        ]);
    }
}