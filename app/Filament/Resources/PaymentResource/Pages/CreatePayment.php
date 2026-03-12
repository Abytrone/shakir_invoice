<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sale;
use App\Traits\RedirectToIndexAfterCreate;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    use RedirectToIndexAfterCreate;

    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['payable_type'] = $data['type'] === Payment::TYPE_INVOICE
            ? Invoice::class
            : Sale::class;
        $data['payable_id'] = $data['type'] === Payment::TYPE_INVOICE
            ? ($data['invoice_id'] ?? null)
            : ($data['sale_id'] ?? null);

        unset($data['invoice_id'], $data['sale_id']);

        return $data;
    }
}
