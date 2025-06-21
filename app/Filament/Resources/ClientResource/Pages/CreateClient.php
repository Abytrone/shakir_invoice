<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Traits\RedirectToIndexAfterCreate;
use Filament\Resources\Pages\CreateRecord;

class CreateClient extends CreateRecord
{
    use RedirectToIndexAfterCreate;

    protected static string $resource = ClientResource::class;
}
