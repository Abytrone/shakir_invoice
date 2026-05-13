<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use App\Traits\RedirectToIndexAfterCreate;
use Filament\Resources\Pages\CreateRecord;

class CreateReceipt extends CreateRecord
{
    use RedirectToIndexAfterCreate;

    protected static string $resource = ReceiptResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ReceiptResource::prepareReceiptData($data);
    }
}
