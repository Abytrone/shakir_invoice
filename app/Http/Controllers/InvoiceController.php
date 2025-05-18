<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceMail;

class InvoiceController extends Controller
{
    public function download(Invoice $invoice)
    {
        $pdf = PDF::loadView('invoices.pdf', ['invoice' => $invoice]);
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