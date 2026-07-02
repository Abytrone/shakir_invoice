<?php

namespace App\Observers;

use App\Constants\PaymentStatus;
use App\Models\Payment;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        if ($payment->status !== PaymentStatus::COMPLETED) {
            return;
        }

        if ($payment->invoice->balance <= 0) {
            $payment->invoice()->update(['status' => 'paid']);
        }
    }
}
