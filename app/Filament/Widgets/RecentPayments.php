<?php

namespace App\Filament\Widgets;

use Filament\Widgets\TableWidget;
use App\Models\Payment;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use App\Models\Client;
use Filament\Tables\Filters\Filter;

class RecentPayments extends TableWidget
{
    protected static ?string $heading = 'Recent Payments';
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 'full';
    protected function getTableQuery(): Builder
    {
        return Payment::query()->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('invoice.client.company_name')
                ->label('Client')
                ->sortable()
                ->searchable(),
            TextColumn::make('amount')
                ->label('Amount')
                ->money('GHS')
                ->searchable(),
            TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'warning',
                    'completed' => 'success',
                    'failed' => 'danger',
                    'refunded' => 'info',
                }),
            TextColumn::make('created_at')
                ->label('Date')
                ->date('M d, Y h:i A')
                ->sortable()
                ->searchable(),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('client_id')
                ->label('Client')
                ->options(Client::all()->pluck('name', 'id'))
                ->searchable(),
            Filter::make('created_at')
        ];
    }
}