<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceMail;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

class InvoiceController extends Controller
{
    public function print(Invoice $invoice)
    {
        $pdf = PDF::loadView('invoices.print', [
            'invoice' => $invoice,
            'client' => $invoice->client,
            'items' => $invoice->items,
        ]);

        return $pdf->stream("Invoice-{$invoice->invoice_number}.pdf");
    }

    public function download(Invoice $invoice)
    {
        $pdf = PDF::loadView('invoices.print', [
            'invoice' => $invoice,
            'client' => $invoice->client,
            'items' => $invoice->items,
        ]);

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    public function send(Invoice $invoice)
    {
        $pdf = PDF::loadView('invoices.pdf', ['invoice' => $invoice]);

        Mail::to($invoice->client->email)
            ->send(new InvoiceMail($invoice, $pdf->output()));

        $invoice->update(['status' => 'sent']);

        return back()->with('success', 'Invoice has been sent successfully.');
    }
}