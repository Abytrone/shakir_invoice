<?php

namespace App\Observers;

use App\Models\Invoice;
use Ramsey\Uuid\Uuid;


class InvoiceObserver
{
    public function creating(Invoice $invoice): void
    {
        $invoice->invoice_uuid = Uuid::uuid4()->toString();
    }
}
