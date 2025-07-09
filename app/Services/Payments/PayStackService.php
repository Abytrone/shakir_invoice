<?php

namespace App\Services\Payments;

use App\Contracts\Payment;
use Illuminate\Support\Facades\Http;

class PayStackService extends Payment
{

    function initialize()
    {
        // TODO: Implement initialize() method.
    }

    function initializeRecurring(): void
    {
        $data = [
            'email' => 'mahmudsheikh25@gmail.com',
            "channel" => "direct_debit",
            'amount' => 100,
            "callback_url" => ""
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.paystack.test_secrete_key'),
        ])->post('https://api.paystack.co/customer/authorization/initialize', $data);

        dump($response->json());

    }

    function process()
    {
        // TODO: Implement process() method.
    }

    function verify()
    {
        // TODO: Implement verify() method.
    }
}
