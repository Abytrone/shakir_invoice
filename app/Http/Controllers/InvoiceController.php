<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

class InvoiceController extends Controller
{
    public function print(Invoice $invoice)
    {
        $invoice->load('items.product');
        $asQuotation = request()->boolean('asQuotation');
        $containsProducts = $invoice->items->contains(function ($item) {
            return $item->product && $item->product->type === 'product';
        });

        $pdf = PDF::loadView('invoices.print', [
            'invoice' => $invoice,
            'client' => $invoice->client,
            'items' => $invoice->items,
            'containsProducts' => $containsProducts,
            'docType' => $asQuotation ? 'QUOTATION' : 'INVOICE'
        ]);
        $name = $asQuotation ? 'quotation' : 'invoice';
        return $pdf->stream("$name-$invoice->invoice_number.pdf");
    }

    public function download(Invoice $invoice)
    {
        $asQuotation = request()->boolean('asQuotation');
        $containsProducts = $invoice->items->contains(function ($item) {
            return $item->type === 'product';
        });
        $pdf = PDF::loadView('invoices.print', [
            'invoice' => $invoice,
            'client' => $invoice->client,
            'items' => $invoice->items,
            'containsProducts' => $containsProducts,
            'docType' => $asQuotation ? 'QUOTATION' : 'INVOICE'
        ]);

        $name = $asQuotation ? 'quotation' : 'invoice';

        return $pdf->download("$name-$invoice->invoice_number.pdf");
    }

    public function generateReceipt(Invoice $invoice)
    {
        $invoice->loadMissing('items.product', 'client');

        $items = $invoice->items->map(fn($item) => [
            'product_id' => $item->product_id,
            'product_name' => $item->product?->name,
            'quantity' => $item->quantity,
            'unit_price' => (float)$item->unit_price,
            'total' => (float)$item->total,
        ])->all();

        $subtotal = $invoice->subtotal;
        $discountAmount = $invoice->discount;
        $taxAmount = $invoice->tax;

        $receipt = Receipt::create([
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'receipt_date' => now(),
            'received_from_name' => $invoice->client->name,
            'received_from_email' => $invoice->client->email,
            'received_from_phone' => $invoice->client->phone,
            'received_from_address' => $invoice->client->address,
            'items' => $items,
            'subtotal' => round($subtotal, 2),
            'discount_rate' => $subtotal > 0 ? round(($discountAmount / $subtotal) * 100, 2) : 0,
            'discount_amount' => round($discountAmount, 2),
            'tax_rate' => $subtotal > 0 ? round(($taxAmount / $subtotal) * 100, 2) : 0,
            'tax_amount' => round($taxAmount, 2),
            'total' => round($invoice->total, 2),
        ]);

        return app(ReceiptController::class)->download($receipt);
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
