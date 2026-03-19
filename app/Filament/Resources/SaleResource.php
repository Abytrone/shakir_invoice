<?php

namespace App\Filament\Resources;

use App\Enums\PaymentMethod;
use App\Filament\Resources\SaleResource\Pages;
use App\Models\Client;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(5)
                    ->schema([
                        // Left 80% (4/5)
                        Forms\Components\Section::make('Sale')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\TextInput::make('reference')
                                    ->label('Sale reference')
                                    ->disabled()
                                    ->visible(fn(string $context): bool => $context === 'edit')
                                    ->dehydrated(false)
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('client_id')
                                    ->options(fn(): array => Client::query()->orderBy('name')->pluck('name', 'id')->toArray())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Client')
                                    ->columnSpanFull(),
                                Forms\Components\Repeater::make('saleItems')
                                    ->relationship()
                                    ->live()
                                    ->schema([
                                        Forms\Components\Select::make('stock_id')
                                            ->label('Product (from stock)')
                                            ->options(
                                                Stock::with('product')
                                                    ->where('quantity', '>', 0)
                                                    ->get()
                                                    ->mapWithKeys(fn(Stock $stock) => [
                                                        $stock->id => $stock->product
                                                            ? "{$stock->product->name} (Stock #{$stock->id}, qty: {$stock->quantity})"
                                                            : "Stock #{$stock->id}",
                                                    ])
                                            )
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                            ->afterStateUpdated(function ($state, Forms\Set $set): void {
                                                if ($state) {
                                                    $stock = Stock::find($state);
                                                    $set('unit_price', $stock?->unit_price ?? 0);
                                                }
                                            })
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make('quantity')
                                            ->required()
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(1)
                                            ->rules([
                                                fn(Get $get, ?SaleItem $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                                    $stockId = $get('stock_id');
                                                    if (!$stockId || !$value) {
                                                        return;
                                                    }

                                                    $stock = Stock::with('product')->find($stockId);
                                                    if (!$stock) {
                                                        return;
                                                    }

                                                    $available = $stock->quantity;

                                                    // When editing an existing item, the stock was already
                                                    // decremented — add back the original allocation.
                                                    if ($record && (int)$record->stock_id === (int)$stockId) {
                                                        $available += $record->quantity;
                                                    }

                                                    if ((int)$value > $available) {
                                                        $name = $stock->product?->name ?? "Stock #{$stockId}";
                                                        $fail("{$name} only has {$available} units available.");
                                                    }
                                                },
                                            ]),
                                        Forms\Components\TextInput::make('unit_price')
                                            ->required()
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->live(),
                                        Forms\Components\TextInput::make('discount')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->live(),
                                    ])
                                    ->columns(3)
                                    ->defaultItems(1)
                                    ->reorderable(false)
                                    ->addActionLabel('Add product')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(3),

                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make('Payments')
                                    ->icon('heroicon-o-banknotes')
                                    ->schema([
                                        Forms\Components\Repeater::make('payments')
                                            ->relationship()
                                            ->schema([
                                                Forms\Components\Select::make('payment_method')
                                                    ->options(PaymentMethod::class)
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function (Forms\Set $set): void {
                                                        $set('payment_source', null);
                                                        $set('source_number', null);
                                                    })
                                                    ->columnSpanFull(),
                                                Forms\Components\TextInput::make('amount')
                                                    ->required()
                                                    ->numeric()
                                                    ->minValue(0.01),
                                                Forms\Components\TextInput::make('payment_source')
                                                    ->label('Source')
                                                    ->placeholder(fn (Get $get): string => PaymentMethod::tryFrom($get('payment_method') ?? '')?->sourcePlaceholder() ?? 'Source name')
                                                    ->required(fn (Get $get): bool => (bool) PaymentMethod::tryFrom($get('payment_method') ?? '')?->requiresSource())
                                                    ->visible(fn (Get $get): bool => (bool) PaymentMethod::tryFrom($get('payment_method') ?? '')?->requiresSource()),
                                                Forms\Components\TextInput::make('source_number')
                                                    ->label('Source No.')
                                                    ->placeholder(fn (Get $get): string => PaymentMethod::tryFrom($get('payment_method') ?? '')?->sourceNumberPlaceholder() ?? 'Number')
                                                    ->required(fn (Get $get): bool => (bool) PaymentMethod::tryFrom($get('payment_method') ?? '')?->requiresSource())
                                                    ->visible(fn (Get $get): bool => (bool) PaymentMethod::tryFrom($get('payment_method') ?? '')?->requiresSource()),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(1)
                                            ->reorderable(false)
                                            ->addActionLabel('Add payment')
                                            ->columnSpanFull()
                                            ->live(),
                                    ]),

                                Forms\Components\Section::make('Summary')
                                    ->icon('heroicon-o-calculator')
                                    ->schema([
                                        Forms\Components\Placeholder::make('summary_display')
                                            ->hiddenLabel()
                                            ->content(function (Get $get): HtmlString {
                                                $items = $get('saleItems') ?? [];
                                                $subtotal = collect($items)->sum(fn($item) => (int)(is_array($item) ? ($item['quantity'] ?? 0) : ($item->quantity ?? 0)) * (float)(is_array($item) ? ($item['unit_price'] ?? 0) : ($item->unit_price ?? 0)));
                                                $discount = collect($items)->sum(fn($item) => (float)(is_array($item) ? ($item['discount'] ?? 0) : ($item->discount ?? 0)));
                                                $total = $subtotal - $discount;

                                                $payments = $get('payments') ?? [];
                                                $paid = collect($payments)->sum(fn($p) => (float)(is_array($p) ? ($p['amount'] ?? 0) : ($p->amount ?? 0)));
                                                $balance = $total - $paid;

                                                $balanceColor = $balance > 0 ? 'text-danger-600 dark:text-danger-400' : 'text-success-600 dark:text-success-400';

                                                return new HtmlString('
                                                    <div class="space-y-2 text-sm">
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                                                            <span>' . number_format($subtotal, 2) . '</span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-500 dark:text-gray-400">Discount</span>
                                                            <span>- ' . number_format($discount, 2) . '</span>
                                                        </div>
                                                        <div class="border-t border-gray-200 dark:border-gray-700 pt-2 flex justify-between font-semibold text-base">
                                                            <span>Total</span>
                                                            <span>' . number_format($total, 2) . '</span>
                                                        </div>
                                                        <div class="border-t border-gray-200 dark:border-gray-700 pt-2 flex justify-between">
                                                            <span class="text-gray-500 dark:text-gray-400">Paid</span>
                                                            <span class="text-success-600 dark:text-success-400">' . number_format($paid, 2) . '</span>
                                                        </div>
                                                        <div class="border-t border-gray-200 dark:border-gray-700 pt-2 flex justify-between font-bold text-base">
                                                            <span>Balance</span>
                                                            <span class="' . $balanceColor . '">' . number_format($balance, 2) . '</span>
                                                        </div>
                                                    </div>
                                                ');
                                            }),
                                    ]),
                            ])
                            ->columnSpan(2),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->getStateUsing(fn(Sale $record): float => $record->total)
                    ->numeric(decimalPlaces: 2)
                    ->sortable(false),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Amount paid')
                    ->getStateUsing(fn(Sale $record): float => $record->amount_paid)
                    ->numeric(decimalPlaces: 2)
                    ->sortable(false),
                Tables\Columns\TextColumn::make('sale_items_count')
                    ->label('Items')
                    ->counts('saleItems')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['saleItems', 'payments']);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}
