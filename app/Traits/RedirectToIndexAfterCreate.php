<?php

namespace App\Traits;

trait RedirectToIndexAfterCreate
{
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
