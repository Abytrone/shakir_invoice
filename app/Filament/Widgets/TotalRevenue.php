<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

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

        $invoiceTotals = Payment::query()
            ->where('payable_type', Invoice::class)
            ->join('invoices', 'invoices.id', '=', 'payments.payable_id')
            ->groupBy('invoices.client_id')
            ->selectRaw('invoices.client_id as client_id, SUM(payments.amount) as total')
            ->pluck('total', 'client_id');
        $saleTotals = Payment::query()
            ->where('payable_type', Sale::class)
            ->join('sales', 'sales.id', '=', 'payments.payable_id')
            ->groupBy('sales.client_id')
            ->selectRaw('sales.client_id as client_id, SUM(payments.amount) as total')
            ->pluck('total', 'client_id');
        $merged = collect();
        foreach ($invoiceTotals as $id => $t) {
            $merged[$id] = ($merged[$id] ?? 0) + (float) $t;
        }
        foreach ($saleTotals as $id => $t) {
            $merged[$id] = ($merged[$id] ?? 0) + (float) $t;
        }
        $topClientId = $merged->sortDesc()->keys()->first();
        $topClient = $topClientId ? Client::find($topClientId) : null;
        $topClientAmount = $topClientId ? ($merged[$topClientId] ?? 0) : 0;

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
                ->description($topClient ? ('GHS ' . number_format($topClientAmount, 2) . ' paid') : 'No payments yet')
                ->color('primary')
                ->icon('heroicon-o-user-group'),
        ];
    }
}