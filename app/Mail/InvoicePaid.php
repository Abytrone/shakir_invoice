<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
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
    public function __construct(public Invoice $invoice, public Model $payment)
    {
        $this->invoiceDownloadUrl = URL::signedRoute('invoices.download', ['invoice' => $invoice]);
        $this->invoicePaymentReceiptUrl = URL::signedRoute('payments.receipt', ['payment' => $payment]);

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
