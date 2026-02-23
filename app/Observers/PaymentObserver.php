<?php

namespace App\Observers;

use App\Models\Payment;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        if ($payment->invoice->balance <= 0) {
            $payment->invoice()->update(['status' => 'paid']);
        }
    }
}
