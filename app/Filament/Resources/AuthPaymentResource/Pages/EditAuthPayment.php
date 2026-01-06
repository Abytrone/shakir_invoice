<?php

namespace App\Filament\Resources\AuthPaymentResource\Pages;

use App\Filament\Resources\AuthPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAuthPayment extends EditRecord
{
    protected static string $resource = AuthPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
