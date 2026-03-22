<?php

namespace App\Filament\Resources;

use App\Enums\AdjustmentReason;
use App\Filament\Resources\StockAdjustmentResource\Pages;
use App\Models\Stock;
use App\Models\StockAdjustment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockAdjustmentResource extends Resource
{
    protected static ?string $model = StockAdjustment::class;

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('stock_id')
                    ->label('Stock item')
                    ->options(
                        Stock::with('product')
                            ->where('quantity', '>', 0)
                            ->get()
                            ->mapWithKeys(fn (Stock $stock) => [
                                $stock->id => $stock->product
                                    ? "{$stock->product->name} (Stock #{$stock->id}, qty: {$stock->quantity})"
                                    : "Stock #{$stock->id}",
                            ])
                    )
                    ->searchable()
                    ->required()
                    ->live()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity to remove')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(function (Forms\Get $get): ?int {
                        $stockId = $get('stock_id');
                        if (! $stockId) {
                            return null;
                        }

                        return Stock::find($stockId)?->quantity;
                    })
                    ->default(1),

                Forms\Components\Select::make('reason')
                    ->options(AdjustmentReason::class)
                    ->required(),

                Forms\Components\Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('stock.product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(40)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Adjusted by')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('reason')
                    ->options(AdjustmentReason::class),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(null);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockAdjustments::route('/'),
            'create' => Pages\CreateStockAdjustment::route('/create'),
        ];
    }
}
