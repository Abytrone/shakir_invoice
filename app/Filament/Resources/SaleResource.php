<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use App\Models\Stock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
                                Forms\Components\TextInput::make('sale_uuid')
                                    ->label('Sale reference')
                                    ->disabled()
                                    ->visible(fn(string $context): bool => $context === 'edit')
                                    ->dehydrated(false)
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('client_id')
                                    ->relationship('client', 'name')
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
                                            ->default(1),
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
                            ->columnSpan(4),

                        // Right 20% (1/5) – Summary
                        Forms\Components\Section::make('Summary')
                            ->icon('heroicon-o-calculator')
                            ->schema([
                                Forms\Components\Placeholder::make('summary_subtotal')
                                    ->label('Subtotal')
                                    ->content(function (Get $get): string {
                                        $items = $get('saleItems') ?? [];
                                        $sum = collect($items)->sum(fn($item) => (int)(is_array($item) ? ($item['quantity'] ?? 0) : ($item->quantity ?? 0)) * (float)(is_array($item) ? ($item['unit_price'] ?? 0) : ($item->unit_price ?? 0)));
                                        return number_format($sum, 2);
                                    }),
                                Forms\Components\Placeholder::make('summary_discount')
                                    ->label('Total discount')
                                    ->content(function (Get $get): string {
                                        $items = $get('saleItems') ?? [];
                                        $sum = collect($items)->sum(fn($item) => (float)(is_array($item) ? ($item['discount'] ?? 0) : ($item->discount ?? 0)));
                                        return number_format($sum, 2);
                                    }),
                                Forms\Components\Placeholder::make('summary_total')
                                    ->label('Total')
                                    ->content(function (Get $get): string {
                                        $items = $get('saleItems') ?? [];
                                        $subtotal = collect($items)->sum(fn($item) => (int)(is_array($item) ? ($item['quantity'] ?? 0) : ($item->quantity ?? 0)) * (float)(is_array($item) ? ($item['unit_price'] ?? 0) : ($item->unit_price ?? 0)));
                                        $discount = collect($items)->sum(fn($item) => (float)(is_array($item) ? ($item['discount'] ?? 0) : ($item->discount ?? 0)));
                                        return number_format($subtotal - $discount, 2);
                                    }),
                            ])
                            ->columnSpan(1),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sale_uuid')
                    ->label('Reference')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
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
