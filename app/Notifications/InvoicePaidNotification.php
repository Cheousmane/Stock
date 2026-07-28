<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class InvoicePaidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly Payment $payment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $customer = $this->invoice->customer;

        return (new MailMessage)
            ->subject("Invoice {$this->invoice->number} has been paid")
            ->greeting("Hello {$notifiable->name},")
            ->line("Invoice {$this->invoice->number} has been fully paid.")
            ->line("Customer: {$customer?->name}")
            ->line("Amount: " . number_format($this->payment->amount_xof / 100, 2) . " XOF")
            ->line("Payment Method: {$this->payment->method}")
            ->action('View Invoice', url("/invoices/{$this->invoice->uuid}"))
            ->line('Thank you for using our application!');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_uuid' => $this->invoice->uuid,
            'invoice_number' => $this->invoice->number,
            'amount' => $this->payment->amount_xof,
            'message' => "Invoice {$this->invoice->number} has been paid.",
        ];
    }
}
