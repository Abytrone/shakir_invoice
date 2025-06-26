<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class InvoicePaid extends Mailable
{
    use Queueable, SerializesModels;

    public string $invoiceDownloadUrl;
    public string $invoicePaymentReceiptUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(public Invoice $invoice, public float $amount)
    {
        $this->invoiceDownloadUrl = URL::signedRoute('invoice.download', ['invoice' => $invoice]);
        $this->invoicePaymentReceiptUrl = URL::signedRoute('payments.receipt', ['invoice' => $invoice]);

    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invoice Paid',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invoice-paid',
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
