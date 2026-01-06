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
            'wumpini6970@gmail.com',
            'AUTH_ozyf7v1e5c----',
            4000
        );

        dd($res->json()['status']);
    }
}
