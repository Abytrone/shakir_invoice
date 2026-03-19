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

    /**
     * @return array<string, string>
     */
    public function sourceOptions(): array
    {
        return match ($this) {
            self::MobileMoney => [
                'MTN MoMo' => 'MTN MoMo',
                'Telecel Cash' => 'Telecel Cash',
                'AirtelTigo Cash' => 'AirtelTigo Cash',
            ],
            self::BankTransfer, self::Cheque => [
                'GCB Bank' => 'GCB Bank',
                'Ecobank Ghana' => 'Ecobank Ghana',
                'Stanbic Bank' => 'Stanbic Bank',
                'Standard Chartered Bank' => 'Standard Chartered Bank',
                'Absa Bank Ghana' => 'Absa Bank Ghana',
                'Fidelity Bank' => 'Fidelity Bank',
                'Consolidated Bank Ghana' => 'Consolidated Bank Ghana (CBG)',
                'CalBank' => 'CalBank',
                'Access Bank Ghana' => 'Access Bank Ghana',
                'Zenith Bank Ghana' => 'Zenith Bank Ghana',
                'Republic Bank' => 'Republic Bank',
                'Prudential Bank' => 'Prudential Bank',
                'First National Bank' => 'First National Bank (FNB)',
                'UBA Ghana' => 'United Bank for Africa (UBA)',
                'Societe Generale Ghana' => 'Societe Generale Ghana',
                'First Atlantic Bank' => 'First Atlantic Bank',
                'Bank of Africa Ghana' => 'Bank of Africa Ghana',
                'National Investment Bank' => 'National Investment Bank (NIB)',
                'Agricultural Development Bank' => 'Agricultural Development Bank (ADB)',
                'OmniBSIC Bank' => 'OmniBSIC Bank',
            ],
            self::Card => [
                'Visa' => 'Visa',
                'Mastercard' => 'Mastercard',
                'Amex' => 'American Express',
            ],
            self::Other => [
                'Other' => 'Other',
            ],
            self::Cash => [],
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
