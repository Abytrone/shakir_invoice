<?php

namespace App\Console\Commands;

use App\Services\PaystackService;
use Illuminate\Console\Command;

class TestAutoBilling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-auto-billing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(PaystackService $paystackService): void
    {
        $res = $paystackService->chargeAuthorization(
            'shakirdynamicsltd@gmail.com',
            'AUTH_7txoafgsgg',
            0.1
        );

        dd($res->json()['status']);
    }
}
