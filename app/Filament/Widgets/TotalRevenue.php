<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Client;

class TotalRevenue extends StatsOverviewWidget
{

    protected static ?int $sort = 1;
    protected function getCards(): array
    {
        $totalRevenue = Payment::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $outstandingCount = Invoice::where('status', '!=', 'paid')->count();
        $outstandingAmount = Invoice::where('status', '!=', 'paid')->get()->sum('total');

        $overdueCount = Invoice::where('status', 'overdue')->count();
        $overdueAmount = Invoice::where('status', 'overdue')->get()->sum('total');

        $topClient = Client::withSum('payments', 'amount')
            ->orderByDesc('payments_sum_amount')
            ->first();

        return [
            Card::make('Total Revenue (This Month)', 'GHS ' . number_format($totalRevenue, 2))
                ->description('Total revenue collected this month')
                ->color('success')
                ->icon('heroicon-o-banknotes'),
            Card::make('Outstanding Invoices', $outstandingCount)
                ->description('GHS ' . number_format($outstandingAmount, 2) . ' outstanding')
                ->color('warning')
                ->icon('heroicon-o-exclamation-circle'),
            Card::make('Overdue Invoices', $overdueCount)
                ->description('GHS ' . number_format($overdueAmount, 2) . ' overdue')
                ->color('danger')
                ->icon('heroicon-o-clock'),
            Card::make('Top Client', $topClient ? $topClient->name : 'N/A')
                ->description($topClient ? ('GHS ' . number_format($topClient->payments_sum_amount, 2) . ' paid') : 'No payments yet')
                ->color('primary')
                ->icon('heroicon-o-user-group'),
        ];
    }
}