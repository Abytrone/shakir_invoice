<?php

namespace App\Filament\Resources\AuthPaymentResource\Pages;

use App\Filament\Resources\AuthPaymentResource;
use App\Traits\RedirectToIndexAfterCreate;
use Filament\Resources\Pages\CreateRecord;

class CreateAuthPayment extends CreateRecord
{
    use RedirectToIndexAfterCreate;

    protected static string $resource = AuthPaymentResource::class;
}
