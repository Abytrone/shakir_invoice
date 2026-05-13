<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceiptResource\Pages;
use App\Models\Client;
use App\Models\Product;
use App\Models\Receipt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;

class ReceiptResource extends Resource
{
    protected static ?string $model = Receipt::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Billing';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Receipt Details')
                    ->icon('heroicon-o-receipt-percent')
                    ->schema([
                        Forms\Components\TextInput::make('receipt_number')
                            ->label('Receipt Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn(string $context): bool => $context === 'edit'),

                        Forms\Components\DateTimePicker::make('receipt_date')
                            ->label('Receipt Date')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),
                    ])->columns(2),

                Forms\Components\Section::make('Received From')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Customer')
                            ->relationship('client', 'name')
                            ->getOptionLabelFromRecordUsing(fn(Client $record): string => $record->company_name
                                ? "{$record->name} ({$record->company_name})"
                                : $record->name)
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn(?string $state, Set $set) => static::fillFromClient($state, $set))
                            ->helperText('Optional. Select a saved customer or leave empty to type details.')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('received_from_name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn(Get $get): bool => filled($get('client_id')))
                            ->dehydrated(),

                        Forms\Components\TextInput::make('received_from_phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(255)
                            ->disabled(fn(Get $get): bool => filled($get('client_id')))
                            ->dehydrated(),

                        Forms\Components\TextInput::make('received_from_email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->disabled(fn(Get $get): bool => filled($get('client_id')))
                            ->dehydrated(),

                        Forms\Components\Textarea::make('received_from_address')
                            ->label('Address')
                            ->rows(2)
                            ->disabled(fn(Get $get): bool => filled($get('client_id')))
                            ->dehydrated()
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('Receipt Items')
                    ->icon('heroicon-o-shopping-cart')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->schema([
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\Select::make('product_id')
                                            ->label('Product')
                                            ->options(fn() => Product::query()
                                                ->where('is_active', true)
                                                ->orderBy('name')
                                                ->pluck('name', 'id'))
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                $product = Product::find($state);

                                                if ($product) {
                                                    $set('product_name', $product->name);
                                                    $set('unit_price', $product->unit_price);
                                                }

                                                static::updateTotals($get, $set);
                                            }),

                                        Forms\Components\Hidden::make('product_name'),

                                        Forms\Components\TextInput::make('quantity')
                                            ->integer()
                                            ->required()
                                            ->default(1)
                                            ->minValue(1)
                                            ->live()
                                            ->afterStateUpdated(fn(Get $get, Set $set) => static::updateTotals($get, $set)),

                                        Forms\Components\TextInput::make('unit_price')
                                            ->numeric()
                                            ->required()
                                            ->default(0)
                                            ->minValue(0)
                                            ->prefix('GHS')
                                            ->live()
                                            ->afterStateUpdated(fn(Get $get, Set $set) => static::updateTotals($get, $set)),
                                    ]),
                            ])
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->columnSpanFull()
                            ->live()
                            ->afterStateUpdated(fn(Get $get, Set $set) => static::updateOuterTotals($get, $set))
                            ->afterStateHydrated(fn(Get $get, Set $set) => static::updateOuterTotals($get, $set)),
                    ]),

                Forms\Components\Section::make('Receipt Summary')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Forms\Components\TextInput::make('discount_rate')
                            ->label('Discount Rate')
                            ->numeric()
                            ->default(0)
                            ->suffix('%')
                            ->live()
                            ->afterStateUpdated(fn(Get $get, Set $set) => static::updateOuterTotals($get, $set)),

                        Forms\Components\TextInput::make('tax_rate')
                            ->label('Tax Rate')
                            ->numeric()
                            ->default(0)
                            ->suffix('%')
                            ->live()
                            ->afterStateUpdated(fn(Get $get, Set $set) => static::updateOuterTotals($get, $set)),

                        Forms\Components\TextInput::make('subtotal')
                            ->numeric()
                            ->prefix('GHS')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('discount_amount')
                            ->numeric()
                            ->prefix('GHS')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('tax_amount')
                            ->numeric()
                            ->prefix('GHS')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('total')
                            ->numeric()
                            ->prefix('GHS')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->recordUrl(fn(Receipt $record): string => URL::signedRoute('receipts.download', $record))
            ->openRecordUrlInNewTab()
            ->columns([
                Tables\Columns\TextColumn::make('receipt_number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->copyable(),

                Tables\Columns\TextColumn::make('client_name')
                    ->label('Received From')
                    ->searchable(['received_from_name']),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Customer')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('receipt_date')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total')
                    ->money('GHS')
                    ->sortable()
                    ->weight('bold'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('print')
                        ->label('Download')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn(Receipt $record): string => URL::signedRoute('receipts.download', $record))
                        ->openUrlInNewTab(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])->icon('heroicon-m-ellipsis-vertical')->tooltip('Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function prepareReceiptData(array $data): array
    {
        $items = collect($data['items'] ?? [])
            ->filter(fn($item): bool => is_array($item) && !empty($item['product_id']) && !empty($item['quantity']))
            ->map(function (array $item): array {
                $product = Product::find($item['product_id']);
                $quantity = (int)($item['quantity'] ?? 1);
                $unitPrice = (float)($item['unit_price'] ?? 0);

                return [
                    'product_id' => (int)$item['product_id'],
                    'product_name' => $item['product_name'] ?? $product?->name,
                    'quantity' => $quantity,
                    'unit_price' => round($unitPrice, 2),
                    'total' => round($quantity * $unitPrice, 2),
                ];
            })
            ->values();

        $subtotal = $items->sum('total');
        $discountRate = (float)($data['discount_rate'] ?? 0);
        $taxRate = (float)($data['tax_rate'] ?? 0);
        $discountAmount = $subtotal * ($discountRate / 100);
        $taxAmount = ($subtotal - $discountAmount) * ($taxRate / 100);

        $data['items'] = $items->all();
        $data['subtotal'] = round($subtotal, 2);
        $data['discount_amount'] = round($discountAmount, 2);
        $data['tax_amount'] = round($taxAmount, 2);
        $data['total'] = round($subtotal - $discountAmount + $taxAmount, 2);

        return $data;
    }

    protected static function fillFromClient(?string $clientId, Set $set): void
    {
        if (!$clientId) {
            return;
        }

        $client = Client::find($clientId);

        if (!$client) {
            return;
        }

        $set('received_from_name', $client->name);
        $set('received_from_phone', $client->phone);
        $set('received_from_email', $client->email);
        $set('received_from_address', $client->address);
    }

    protected static function updateTotals(Get $get, Set $set): void
    {
        static::updateTotalsFromState(
            $set,
            $get('../../items') ?? [],
            (float)$get('../../discount_rate'),
            (float)$get('../../tax_rate'),
            '../../'
        );
    }

    protected static function updateOuterTotals(Get $get, Set $set): void
    {
        static::updateTotalsFromState(
            $set,
            $get('items') ?? [],
            (float)$get('discount_rate'),
            (float)$get('tax_rate')
        );
    }

    protected static function updateTotalsFromState(Set $set, array $items, float $discountRate, float $taxRate, string $prefix = ''): void
    {
        $subtotal = collect($items)
            ->filter(fn($item): bool => is_array($item))
            ->sum(fn(array $item): float => (float)($item['unit_price'] ?? 0) * (int)($item['quantity'] ?? 0));
        $discountAmount = $subtotal * ($discountRate / 100);
        $taxAmount = ($subtotal - $discountAmount) * ($taxRate / 100);
        $total = $subtotal - $discountAmount + $taxAmount;

        $set($prefix . 'subtotal', round($subtotal, 2));
        $set($prefix . 'discount_amount', round($discountAmount, 2));
        $set($prefix . 'tax_amount', round($taxAmount, 2));
        $set($prefix . 'total', round($total, 2));
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
            'index' => Pages\ListReceipts::route('/'),
            'create' => Pages\CreateReceipt::route('/create'),
            'edit' => Pages\EditReceipt::route('/{record}/edit'),
        ];
    }
}
