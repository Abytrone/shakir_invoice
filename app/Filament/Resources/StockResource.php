<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockResource\Pages;
use App\Models\Product;
use App\Models\Stock;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;

class StockResource extends Resource
{
    protected static ?string $model = Stock::class;
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function formSchema(): array
    {
        return [

        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->live()
                    ->required()
                    ->columnSpanFull()
                    ->afterStateUpdated(function ($state, Forms\Set $set): void {
                        if ($state) {
                            $product = Product::find($state);
                            $set('unit_price', $product?->unit_price ?? '');
                        }
                    }),
                Forms\Components\TextInput::make('unit_price')
                    ->required()
                    ->numeric()
                    ->columnSpanFull()
                    ->default(fn(Forms\Get $get): mixed => $get('product_id') ? Product::find($get('product_id'))?->unit_price : null),

                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->columnSpanFull(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')

                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
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
                ActionGroup::make([
                    Tables\Actions\Action::make('increaseStock')
                        ->label('Increase stock')
                        ->icon('heroicon-o-plus')
                        ->modalWidth(MaxWidth::Small)
                        ->form([
                            Forms\Components\TextInput::make('quantity')
                                ->label('Quantity to add')
                                ->required()
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->columnSpanFull(),
                        ])
                        ->action(function (Stock $record, array $data): void {
                            $added = (int) $data['quantity'];
                            $record->quantity += $added;
                            $record->save();

                            Notification::make()
                                ->title('Stock increased')
                                ->body("Added {$added} unit(s). New quantity: {$record->quantity}.")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Actions'),
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
            'index' => Pages\ListStocks::route('/'),
//            'create' => Pages\CreateStock::route('/create'),
//            'edit' => Pages\EditStock::route('/{record}/edit'),
        ];
    }
}
