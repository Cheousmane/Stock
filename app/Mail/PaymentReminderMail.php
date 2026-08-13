<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class PaymentReminderMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly int $reminderLevel,
    ) {}

    public function envelope(): Envelope
    {
        $labels = [1 => 'Première relance', 2 => 'Deuxième relance', 3 => 'Dernière relance'];

        return new Envelope(
            subject: "Facture {$this->invoice->number} — " . ($labels[$this->reminderLevel] ?? 'Relance'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-reminder',
            with: [
                'invoice' => $this->invoice,
                'customer' => $this->invoice->customer,
                'company' => $this->invoice->company,
                'dueAmount' => $this->invoice->balance_due_xof,
                'reminderLevel' => $this->reminderLevel,
                'invoiceUrl' => config('app.frontend_url', config('app.url')).'/invoices/'.$this->invoice->uuid,
            ],
        );
    }
}