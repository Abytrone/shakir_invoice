<?php

namespace App\Filament\Widgets;

use Filament\Widgets\PieChartWidget;
use App\Models\Invoice;

class InvoicesStatusChart extends PieChartWidget
{
    protected static ?string $heading = 'Invoices by Status';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $statuses = ['Paid', 'Unpaid', 'Overdue'];
        $data = [];
        foreach ($statuses as $status) {
            $data[] = Invoice::where('status', $status)->count();
        }
        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => ['#22c55e', '#f59e42', '#ef4444'],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $statuses,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}