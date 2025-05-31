<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

//    protected function mutateFormDataBeforeCreate(array $data): array
//    {
//dd($data);
//        $data['total'] = $data['grand_total'];
//        return $data;
//    }
}
