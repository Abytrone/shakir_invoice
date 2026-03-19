<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel, HasIcon, HasColor
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Card = 'card';
    case MobileMoney = 'mobile_money';
    case Cheque = 'cheque';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::BankTransfer => 'Bank Transfer',
            self::Card => 'Credit Card',
            self::MobileMoney => 'Mobile Money',
            self::Cheque => 'Cheque',
            self::Other => 'Other',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Cash => 'heroicon-m-banknotes',
            self::BankTransfer => 'heroicon-m-building-library',
            self::Card => 'heroicon-m-credit-card',
            self::MobileMoney => 'heroicon-m-device-phone-mobile',
            self::Cheque => 'heroicon-m-document-check',
            self::Other => 'heroicon-m-question-mark-circle',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Cash => 'success',
            self::BankTransfer => 'info',
            self::Card => 'primary',
            self::MobileMoney => 'warning',
            self::Cheque => 'gray',
            self::Other => 'gray',
        };
    }

    public function requiresSource(): bool
    {
        return $this !== self::Cash;
    }

    public function sourcePlaceholder(): string
    {
        return match ($this) {
            self::Cash => '',
            self::BankTransfer => 'e.g., Ecobank, GCB Bank',
            self::Card => 'e.g., Visa, Mastercard',
            self::MobileMoney => 'e.g., MTN MoMo, Vodafone Cash',
            self::Cheque => 'e.g., Issuing bank name',
            self::Other => 'Source name',
        };
    }

    public function sourceNumberPlaceholder(): string
    {
        return match ($this) {
            self::Cash => '',
            self::BankTransfer => 'Bank account number',
            self::Card => 'Last 4 digits of card',
            self::MobileMoney => 'Mobile number',
            self::Cheque => 'Cheque number',
            self::Other => 'Reference number',
        };
    }
}
