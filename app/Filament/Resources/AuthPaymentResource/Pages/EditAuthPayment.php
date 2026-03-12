<?php

namespace App\Filament\Resources\AuthPaymentResource\Pages;

use App\Filament\Resources\AuthPaymentResource;
use App\Traits\RedirectToIndexAfterCreate;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAuthPayment extends EditRecord
{
    use RedirectToIndexAfterCreate;

    protected static string $resource = AuthPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
