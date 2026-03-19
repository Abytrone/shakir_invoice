<?php

namespace App\Observers;

use App\Models\Payment;

class PaymentObserver
{
    public function creating(Payment $payment): void
    {
        if ($payment->client_payment_source_id) {
            $source = $payment->clientPaymentSource;
            if ($source) {
                $payment->payment_source = $source->label;
                $payment->source_number = $source->source_number;
            }
        }
    }

    public function created(Payment $payment): void
    {
        if (empty($payment->reference_number)) {
            $payment->reference_number = 'PAY' . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
            $payment->saveQuietly();
        }

        $payable = $payment->payable;
        if ($payable instanceof \App\Models\Invoice && $payable->balance <= 0) {
            $payable->update(['status' => 'paid']);
        }
    }

    public function updating(Payment $payment): void
    {
        if ($payment->isDirty('client_payment_source_id') && $payment->client_payment_source_id) {
            $source = $payment->clientPaymentSource;
            if ($source) {
                $payment->payment_source = $source->label;
                $payment->source_number = $source->source_number;
            }
        }
    }
}
