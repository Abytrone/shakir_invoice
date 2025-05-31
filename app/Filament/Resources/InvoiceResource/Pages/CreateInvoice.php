<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Traits\RedirectToIndexAfterCreate;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    use RedirectToIndexAfterCreate;

    protected static string $resource = InvoiceResource::class;

}
