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
                Forms\Components\Select::make('invoice_id')
                    ->relationship('invoice', 'invoice_number')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('₵'),
                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Date')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'card' => 'Credit Card',
                        'mobile_money' => 'Mobile Money',
                        'other' => 'Other',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('reference_number')
                    ->maxLength(255),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ])
                    ->required()
                    ->default('completed'),
            ]);
    }

    public static function table(Table $table): Table
    {
        $paymentLabels = [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'card' => 'Credit Card',
            'mobile_money' => 'Mobile Money',
            'other' => 'Other',
        ];

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('GHS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date('M d, Y h:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $paymentLabels[$state])
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('reference_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
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
                        ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                        ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
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
                // Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('receipt')
                        ->icon('heroicon-o-printer')
                        ->url(fn (Payment $record): string => URL::signedRoute('payments.receipt', $record))
                        ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
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
