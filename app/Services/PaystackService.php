<?php

namespace App\Services;

use GuzzleHttp\Promise\PromiseInterface;
use Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class PaystackService
{

    public function createAuthorization($email, $authorizationCode, $amount)
    {
        $data = [
            'email' => $email,
            'amount' => $amount,
            'authorization_code' => $authorizationCode,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.paystack.live_secret_key'),
        ])->post('https://api.paystack.co/transaction/charge_authorization', $data);

        $res = json_decode($response, true);
        if (!$res['status']) {
            return view('payments.success', [
                'invoice' => null,
                'message' => 'Failed to initialize payment.',
            ]);
        }
    }

    public function chargeAuthorization($email, $authorizationCode, $amount)
    {
        $data = [
            'email' => $email,
            'amount' => $amount,
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


}
