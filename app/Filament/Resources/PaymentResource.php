<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Billing';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Details')
                    ->icon('heroicon-o-credit-card')
                    ->description('Enter the payment information below.')
                    ->schema([
                        Forms\Components\Select::make('invoice_id')
                            ->relationship('invoice', 'invoice_number')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Invoice Number')
                            ->prefixIcon('heroicon-m-document-text'),

                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('₵')
                            ->label('Amount Paid'),

                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Payment Date')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->prefixIcon('heroicon-m-calendar'),

                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'cash' => 'Cash',
                                'bank_transfer' => 'Bank Transfer',
                                'card' => 'Credit Card',
                                'mobile_money' => 'Mobile Money',
                                'cheque' => 'Cheque',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->prefixIcon('heroicon-m-banknotes'),
                    ])->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'refunded' => 'Refunded',
                            ])
                            ->required()
                            ->default('completed')
                            ->prefixIcon('heroicon-m-flag'),

                        Forms\Components\TextInput::make('reference_number')
                            ->maxLength(255)
                            ->label('Reference No.')
                            ->placeholder('e.g., TRX-123456789'),

                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->placeholder('Any additional notes...'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $paymentLabels = [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'card' => 'Credit Card',
            'cheque' => 'Cheque',
            'mobile_money' => 'Mobile Money',
            'other' => 'Other',
        ];

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('amount')
                    ->money('GHS')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => $paymentLabels[$state] ?? $state)
                    ->icon(fn(string $state): string => match ($state) {
                        'cash' => 'heroicon-m-banknotes',
                        'bank_transfer' => 'heroicon-m-building-library',
                        'card' => 'heroicon-m-credit-card',
                        'mobile_money' => 'heroicon-m-device-phone-mobile',
                        'other' => 'heroicon-m-question-mark-circle',
                        default => 'heroicon-m-banknotes',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'cash' => 'success',
                        'bank_transfer' => 'info',
                        'card' => 'primary',
                        'mobile_money' => 'warning',
                        'other' => 'gray',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date('M d, Y h:i A')
                    ->sortable()
                    ->icon('heroicon-m-calendar'),

                Tables\Columns\TextColumn::make('reference_number')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->icon(fn(string $state): string => match ($state) {
                        'pending' => 'heroicon-m-clock',
                        'completed' => 'heroicon-m-check-circle',
                        'failed' => 'heroicon-m-x-circle',
                        'refunded' => 'heroicon-m-arrow-uturn-left',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'info',
                    }),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')->label('From date'),
                        DatePicker::make('until')->label('To date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'card' => 'Credit Card',
                        'mobile_money' => 'Mobile Money',
                        'other' => 'Other',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('receipt')
                        ->icon('heroicon-o-printer')
                        ->url(fn(Payment $record): string => URL::signedRoute('payments.receipt', $record))
                        ->openUrlInNewTab(),
                    Tables\Actions\DeleteAction::make(),
                ])->icon('heroicon-m-ellipsis-vertical')->tooltip('Actions'),
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            //            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
