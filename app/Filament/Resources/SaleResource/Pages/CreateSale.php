<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Traits\RedirectToIndexAfterCreate;
use Filament\Resources\Pages\CreateRecord;

class CreateSale extends CreateRecord
{
    use RedirectToIndexAfterCreate;

    protected static string $resource = SaleResource::class;
}
