<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Mail\InvoiceSent;
use App\Traits\RedirectToIndexAfterCreate;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;

class CreateInvoice extends CreateRecord
{
    use RedirectToIndexAfterCreate;

    protected static string $resource = InvoiceResource::class;

    protected function afterCreate(): void
    {
        $this->record->load('client');
        if($this->record->isSent()) {
            Mail::to($this->record->client->email)
                ->send(new InvoiceSent($this->record));
        }
    }
}
