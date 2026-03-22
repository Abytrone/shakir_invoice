<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AdjustmentReason: string implements HasLabel, HasIcon, HasColor
{
    case Damaged = 'damaged';
    case Expired = 'expired';
    case SupplierReturn = 'supplier_return';
    case InventoryCorrection = 'inventory_correction';
    case Theft = 'theft';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Damaged => 'Damaged',
            self::Expired => 'Expired',
            self::SupplierReturn => 'Supplier Return',
            self::InventoryCorrection => 'Inventory Correction',
            self::Theft => 'Theft / Shrinkage',
            self::Other => 'Other',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Damaged => 'heroicon-m-exclamation-triangle',
            self::Expired => 'heroicon-m-clock',
            self::SupplierReturn => 'heroicon-m-arrow-uturn-left',
            self::InventoryCorrection => 'heroicon-m-adjustments-horizontal',
            self::Theft => 'heroicon-m-shield-exclamation',
            self::Other => 'heroicon-m-question-mark-circle',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Damaged => 'danger',
            self::Expired => 'warning',
            self::SupplierReturn => 'info',
            self::InventoryCorrection => 'gray',
            self::Theft => 'danger',
            self::Other => 'gray',
        };
    }
}
