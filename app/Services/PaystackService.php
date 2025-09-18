<?php

namespace App\Services;

use Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    public function getAuthorizationUrl(string $email, string $phone)
    {

        $data = [
            'email' => $email,
            'mobile' => $phone,
            'amount' => 100, // 1 GHS in pesewas
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
