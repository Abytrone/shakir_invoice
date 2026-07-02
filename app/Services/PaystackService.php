<?php

namespace App\Services;

use App\Constants\InvoiceStatus;
use App\Mail\InvoicePaid;
use App\Models\Client;
use App\Models\Invoice;
use Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaystackService
{
    public function getAuthorizationUrl(string $email, string $phone, float $amount)
    {

        $data = [
            'email' => $email,
            'mobile' => $phone,
            'amount' => $amount * 100,
            'metadata' => [
                'custom_fields' => [
                    [
                        'display_name' => 'Auth Email',
                        'variable_name' => 'auth_email',
                        'value' => $email,
                    ],
                ],
            ],
        ];

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.paystack.live_secret_key'),
        ])->post('https://api.paystack.co/transaction/initialize', $data);

        $res = json_decode($response, true);
        if (!$res['status']) {
            return null;
        }

        return $res['data'];

    }

    public function chargeAuthorization($email, $authorizationCode, $amount)
    {
        $intAmount = ceil($amount * 100);
        $data = [
            'email' => $email,
            'amount' => (int)$intAmount,
            'authorization_code' => $authorizationCode,
        ];

        try {
            return Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.paystack.live_secret_key'),
            ])->post('https://api.paystack.co/transaction/charge_authorization', $data);
        } catch (\Exception $e) {
            Log::info('failed to charge authorization: ' . $e->getMessage());
            return null;
        }


    }

    public function verify($ref)
    {
        try {
            return Http::withHeaders(['Authorization' => 'Bearer ' . config('services.paystack.live_secret_key')])
                ->get('https://api.paystack.co/transaction/verify/' . $ref);
        } catch (\Exception $e) {
            Log::info('failed to verify payment: ' . $e->getMessage());
            return null;
        }
    }

    public function verifyV2($ref)
    {
        try {
            return Http::withHeaders(['Authorization' => 'Bearer ' . config('services.paystack.live_secret_key')])
                ->get('https://api.paystack.co/transaction/verify/' . $ref);
        } catch (\Exception $e) {
            Log::info('failed to verify payment: ' . $e->getMessage());
            return null;
        }
    }

    public function refundPayment(string $reference, float|int $param)
    {
        try {
            $data = [
                'transaction' => $reference,
                'amount' => $param,
            ];
            return Http::withHeaders(['Authorization' => 'Bearer ' . config('services.paystack.live_secret_key')])
                ->post('https://api.paystack.co/refund', $data);
        } catch (\Exception $e) {
            Log::info('failed to refund payment: ' . $e->getMessage());
            return null;
        }

    }

    public function savePaymentAfterVerification($response, Invoice $invoice): bool
    {
        $amount = $response['data']['amount'] / 100;
        $channel = $response['data']['channel'];

        $payment = $invoice->payments()
            ->firstOrCreate(
                ['reference_number' => $response['data']['reference']], [
                'amount' => $amount,
                'notes' => '...',
                'payment_method' => $channel,
            ]);

        if ($invoice->isPaid()) {
            $invoice->update(['status' => InvoiceStatus::PAID]);
        }

        if ($invoice->isPartial()) {
            $invoice->update(['status' => InvoiceStatus::PARTIAL]);
        }

        if ($invoice->client->hasEmail()) {
            try {
                Mail::to($invoice->client->email)->send(new InvoicePaid($invoice, $payment, $amount));
            } catch (\Exception $e) {
                Log::info('failed to send email: ' . $e->getMessage());
            }
        }

        return true;
    }


}
