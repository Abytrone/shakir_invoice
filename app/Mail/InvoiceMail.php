<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;

    private $pdfContent;

    public function __construct(Invoice $invoice, $pdfContent)
    {
        $this->invoice = $invoice;
        $this->pdfContent = $pdfContent;
    }

    public function build()
    {
        return $this->subject("Invoice {$this->invoice->invoice_number} from Your Company")
            ->view('emails.invoice')
            ->attachData($this->pdfContent, "invoice-{$this->invoice->invoice_number}.pdf", [
                'mime' => 'application/pdf',
            ]);
    }
}
