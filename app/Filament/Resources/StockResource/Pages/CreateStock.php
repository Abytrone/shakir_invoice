<?php

namespace App\Filament\Resources\StockResource\Pages;

use App\Filament\Resources\StockResource;
use App\Traits\RedirectToIndexAfterCreate;
use Filament\Resources\Pages\CreateRecord;

class CreateStock extends CreateRecord
{
    use RedirectToIndexAfterCreate;

    protected static string $resource = StockResource::class;
}
