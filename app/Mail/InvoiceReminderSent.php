<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class InvoiceReminderSent extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public string $invoicePaymentInitUrl;
    public string $invoiceDownloadUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(public Invoice $invoice, public int $daysBeforeDueDate)
    {
        $this->invoicePaymentInitUrl = URL::signedRoute('payments.initialize', ['invoice' => $invoice]);
        $this->invoiceDownloadUrl = URL::signedRoute('invoice.download', ['invoice' => $invoice]);

    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invoice Reminder',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invoice-reminder-sent',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
