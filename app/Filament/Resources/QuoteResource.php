<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuoteResource\Pages;
use App\Filament\Resources\QuoteResource\RelationManagers;
use App\Models\Quote;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'Billing';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Quote Details')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('quote_number')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->disabled()
                            ->dehydrated(false)
                            ->default('Will be auto-generated'),
                        Forms\Components\DatePicker::make('issue_date')
                            ->required(),
                        Forms\Components\DatePicker::make('expiry_date')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'sent' => 'Sent',
                                'accepted' => 'Accepted',
                                'declined' => 'Declined',
                                'expired' => 'Expired',
                            ])
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->preload()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if (!$state) return;
                                        
                                        $product = Product::find($state);
                                        $set('description', $product->description);
                                        $set('unit_price', $product->unit_price);
                                        $set('tax_rate', $product->tax_rate);

                                        // Calculate line totals
                                        $quantity = $set('quantity') ?? 1;
                                        $unitPrice = $product->unit_price;
                                        $taxRate = $product->tax_rate;
                                        
                                        $subtotal = $quantity * $unitPrice;
                                        $taxAmount = $subtotal * ($taxRate / 100);
                                        $total = $subtotal + $taxAmount;

                                        $set('subtotal', $subtotal);
                                        $set('tax_amount', $taxAmount);
                                        $set('total', $total);
                                    }),
                                Forms\Components\TextInput::make('description')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('quantity')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                        $quantity = $state ?? 1;
                                        $unitPrice = $get('unit_price') ?? 0;
                                        $taxRate = $get('tax_rate') ?? 0;
                                        
                                        $subtotal = $quantity * $unitPrice;
                                        $taxAmount = $subtotal * ($taxRate / 100);
                                        $total = $subtotal + $taxAmount;

                                        $set('subtotal', $subtotal);
                                        $set('tax_amount', $taxAmount);
                                        $set('total', $total);
                                    }),
                                Forms\Components\TextInput::make('unit_price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('₵')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                        $unitPrice = $state ?? 0;
                                        $quantity = $get('quantity') ?? 1;
                                        $taxRate = $get('tax_rate') ?? 0;
                                        
                                        $subtotal = $quantity * $unitPrice;
                                        $taxAmount = $subtotal * ($taxRate / 100);
                                        $total = $subtotal + $taxAmount;

                                        $set('subtotal', $subtotal);
                                        $set('tax_amount', $taxAmount);
                                        $set('total', $total);
                                    }),
                                Forms\Components\TextInput::make('tax_rate')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('%')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                        $taxRate = $state ?? 0;
                                        $quantity = $get('quantity') ?? 1;
                                        $unitPrice = $get('unit_price') ?? 0;
                                        
                                        $subtotal = $quantity * $unitPrice;
                                        $taxAmount = $subtotal * ($taxRate / 100);
                                        $total = $subtotal + $taxAmount;

                                        $set('subtotal', $subtotal);
                                        $set('tax_amount', $taxAmount);
                                        $set('total', $total);
                                    }),
                                Forms\Components\TextInput::make('subtotal')
                                    ->numeric()
                                    ->prefix('₵')
                                    ->disabled(),
                                Forms\Components\TextInput::make('tax_amount')
                                    ->numeric()
                                    ->prefix('₵')
                                    ->disabled(),
                                Forms\Components\TextInput::make('total')
                                    ->numeric()
                                    ->prefix('₵')
                                    ->disabled(),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->columnSpanFull()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                // Calculate quote totals
                                $items = collect($state ?? []);
                                
                                $subtotal = $items->sum('subtotal');
                                $taxAmount = $items->sum('tax_amount');
                                $discountRate = $get('discount_rate') ?? 0;
                                
                                $discountAmount = $subtotal * ($discountRate / 100);
                                $total = $subtotal + $taxAmount - $discountAmount;

                                $set('subtotal', $subtotal);
                                $set('tax_amount', $taxAmount);
                                $set('discount_amount', $discountAmount);
                                $set('total', $total);
                            }),
                    ]),

                Forms\Components\Section::make('Totals')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->required()
                            ->numeric()
                            ->prefix('₵')
                            ->disabled(),
                        Forms\Components\TextInput::make('tax_amount')
                            ->required()
                            ->numeric()
                            ->prefix('₵')
                            ->disabled(),
                        Forms\Components\TextInput::make('discount_rate')
                            ->label('Discount (%)')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->suffix('%')
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                $discountRate = $state ?? 0;
                                $subtotal = $get('subtotal') ?? 0;
                                
                                $discountAmount = $subtotal * ($discountRate / 100);
                                $total = $subtotal + ($get('tax_amount') ?? 0) - $discountAmount;

                                $set('discount_amount', $discountAmount);
                                $set('total', $total);
                            }),
                        Forms\Components\TextInput::make('discount_amount')
                            ->required()
                            ->numeric()
                            ->prefix('₵')
                            ->disabled(),
                        Forms\Components\TextInput::make('total')
                            ->required()
                            ->numeric()
                            ->prefix('₵')
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('terms')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quote_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subtotal')
                    ->money('GHS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_amount')
                    ->money('GHS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tax_amount')
                    ->money('GHS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->money('GHS')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('GHS'),
                    ]),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'info',
                        'accepted' => 'success',
                        'declined' => 'danger',
                        'expired' => 'warning',
                    }),
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
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'accepted' => 'Accepted',
                        'declined' => 'Declined',
                        'expired' => 'Expired',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-download')
                    ->url(fn (Quote $record): string => route('quotes.download', $record))
                    ->openUrlInNewTab()
                    ->visible(fn () => Auth::user()->can('download_quote')),
                Tables\Actions\Action::make('send')
                    ->icon('heroicon-o-envelope')
                    ->action(fn (Quote $record) => $record->send())
                    ->requiresConfirmation()
                    ->visible(fn () => Auth::user()->can('send_quote')),
                Tables\Actions\Action::make('convert_to_invoice')
                    ->icon('heroicon-o-document-text')
                    ->action(fn (Quote $record) => $record->convertToInvoice())
                    ->requiresConfirmation()
                    ->visible(fn (Quote $record) => $record->status === 'accepted' && Auth::user()->can('convert_quote')),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListQuotes::route('/'),
            'create' => Pages\CreateQuote::route('/create'),
            'edit' => Pages\EditQuote::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}