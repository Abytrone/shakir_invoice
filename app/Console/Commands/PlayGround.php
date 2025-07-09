<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class PlayGround extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:play-ground';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {

        $this->initializeRecurring();

//        $data = [
//            'email' => 'mahmudsheikh25@gmail.com',
//            'channels' => ['bank'],
//            'amount' => 100,
//            "metadata" => [
//                "custom_filters" => [
//                    "recurring" => true
//                ]
//            ]
//        ];
//
//        $response = Http::withHeaders([
//            'Authorization' => 'Bearer ' . config('services.paystack.test_secrete_key'),
//        ])->post('https://api.paystack.co/transaction/initialize', $data);
//
//        dump($response->json());
    }

    function initializeRecurring(): void
    {
        $data = [
            'email' => 'mahmudsheikh25@gmail.com',
            "channel" => "direct_debit",
            'amount' => 100,
            "callback_url" => "http://localhost:8000/payments/recurring/callback",
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.paystack.test_secrete_key'),
        ])->post('https://api.paystack.co/customer/authorization/initialize', $data);

        dump($response->json());

    }
}
