<?php

namespace App\Services;

use GuzzleHttp\Promise\PromiseInterface;
use Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class PaystackService
{

    public function chargeAuthorization($email, $authorizationCode, $amount)
    {
        $data = [
            'email' => $email,
            'amount' => $amount * 100,
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


}
