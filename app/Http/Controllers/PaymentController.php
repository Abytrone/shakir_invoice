<?php

namespace App\Http\Controllers;

use App\Mail\InvoicePaid;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    public function initialize(Invoice $invoice, Request $request)
    {
        if ($invoice->isPaid()) {
            return view('payments.success', [
                'invoice' => $invoice,
                'message' => 'This invoice has already been paid.',
            ]);
        }
        Log::info('amount to pay: '.$invoice->amount_to_pay * 100);
        $remainingBalance = $invoice->amount_to_pay * 100;
        $data = [
            'email' => $invoice->client->email,
            'mobile' => $invoice->client->phone,
            'amount' => (int) $remainingBalance,
            'metadata' => [
                'custom_fields' => [
                    [
                        'display_name' => 'Full Name',
                        'variable_name' => 'fullname',
                        'value' => $invoice->client->name,
                    ], [
                        'display_name' => 'Invoice No.',
                        'variable_name' => 'invoice_number',
                        'value' => $invoice->invoice_number,
                    ],
                ],
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.paystack.test_key'),
        ])->post('https://api.paystack.co/transaction/initialize', $data);

        $res = json_decode($response, true);
        if (! $res['status']) {
            return view('payments.success', [
                'invoice' => null,
                'message' => 'Failed to initialize payment.',
            ]);
        }
        Log::info('reference: '.$res['data']['reference']);

        return redirect($res['data']['authorization_url']);

    }

    public function process(Request $request)
    {

        info('Processing payment for reference: '.$request->reference);
        $ref = $request->reference;

        $response = Http::withHeaders(['Authorization' => 'Bearer '.config('services.paystack.test_key')])
            ->get('https://api.paystack.co/transaction/verify/'.$ref);

        if (! $response['status']) {
            return view('payments.success', [
                'invoice' => null,
                'message' => 'There was an error processing your payment. Please try again.',
            ]);
        }

        $invoiceNumber = $response['data']['metadata']['custom_fields'][1]['value'];
        $amount = $response['data']['amount'] / 100;
        $channel = $response['data']['channel'];

        $invoice = Invoice::where('invoice_number', $invoiceNumber)->first();

        if (! $invoice) {
            return view('payments.success', [
                'invoice' => null,
                'message' => 'Invoice not found. Please check the invoice number and try again.',
            ]);
        }

        $invoice->payments()
            ->firstOrCreate(
                ['reference_number' => $ref], [
                    'amount' => $amount,
                    'note' => '...',
                    'payment_method' => $channel,
                ]);

        if ($invoice->isPaid()) {
            $invoice->update(['status' => 'paid']);
        }

        if ($invoice->isPartial()) {
            $invoice->update(['status' => 'partial']);
        }

        Mail::to($invoice)->send(new InvoicePaid($invoice, $amount));

        info('Payment processed successfully for invoice: '.$invoice->invoice_number);
        return view('payments.success', [
            'invoice' => $invoice,
            'message' => 'Invoice payment has been processed successfully.',
        ]);
    }
}
