<?php

namespace App\Observers;

use App\Models\Payment;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        $payable = $payment->payable;
        if ($payable instanceof \App\Models\Invoice && $payable->balance <= 0) {
            $payable->update(['status' => 'paid']);
        }
    }
}
