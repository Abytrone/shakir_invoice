<?php

namespace App\Filament\Widgets;

use Filament\Widgets\LineChartWidget;
use App\Models\Payment;

class MonthlyRevenueTrend extends LineChartWidget
{
    protected static ?string $heading = 'Monthly Revenue Trend';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $months = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];
        $data = [];
        foreach (range(1, 12) as $month) {
            $data[] = Payment::whereMonth('created_at', $month)
                ->whereYear('created_at', now()->year)
                ->sum('amount');
        }
        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $data,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34,197,94,0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $months,
        ];
    }
}