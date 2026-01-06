<?php

namespace App\Console\Commands;

use App\Models\AuthPayment;
use App\Services\PaystackService;
use Illuminate\Console\Command;

class RefundAuthPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:refund-auth-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(PaystackService $paystackService)
    {
//        info('Getting Refunding payment');
//        $p = AuthPayment::query()->get();

//        $re = $paystackService->refundPayment('cvsqx1tpu5', 0.10 * 100);
//        $res = json_decode($re, true);
//        dd($res);
//        foreach ($p as $payment) {
//            info('Refunding payment', [$payment]);
//
//        }
    }
}
