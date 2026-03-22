<?php

namespace App\Filament\Resources;

use App\Constants\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\ClientPaymentSource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sale;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
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
                        Forms\Components\Select::make('type')
                            ->options([
                                Payment::TYPE_INVOICE => 'Invoice',
                                Payment::TYPE_SALES => 'Sale',
                            ])
                            ->default(Payment::TYPE_INVOICE)
                            ->required()
                            ->live()
                            ->label('Payment for')
                            ->prefixIcon('heroicon-m-document-text')
                            ->afterStateUpdated(function ($state, Forms\Set $set): void {
                                $set('invoice_id', null);
                                $set('sale_id', null);
                            }),

                        Forms\Components\Select::make('invoice_id')
                            ->options(function (): array {
                                return Invoice::query()
                                    ->where('status', '!=', InvoiceStatus::PAID)
                                    ->pluck('invoice_number', 'id')->toArray();
                            })
                            ->required(fn(Get $get): bool => $get('type') === Payment::TYPE_INVOICE)
                            ->searchable()
                            ->preload()
                            ->label('Invoice Number')
                            ->prefixIcon('heroicon-m-document-text')
                            ->visible(fn(Get $get): bool => $get('type') === Payment::TYPE_INVOICE)
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('client_payment_source_id', null))
                            ->dehydrated(),

                        Forms\Components\Select::make('sale_id')
                            ->options(function (): array {
                                return Sale::query()->pluck('reference', 'id')->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->label('Sale')
                            ->prefixIcon('heroicon-m-shopping-cart')
                            ->required(fn(Get $get): bool => $get('type') === Payment::TYPE_SALES)
                            ->visible(fn(Get $get): bool => $get('type') === Payment::TYPE_SALES)
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('client_payment_source_id', null)),

                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('₵')
                            ->label('Amount Paid')
                            ->rules([
                                fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    if ($get('type') !== Payment::TYPE_INVOICE) {
                                        return;
                                    }
                                    $invoiceId = $get('invoice_id');
                                    if (!$invoiceId) {
                                        return;
                                    }
                                    $invoice = Invoice::with('items')->where('id', $invoiceId)->first();
                                    if ($invoice && $value > $invoice->balance) {
                                        $fail("The :attribute must be less than or equal to {$invoice->balance}.");
                                    }
                                },
                            ]),

                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Payment Date')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->prefixIcon('heroicon-m-calendar'),

                        Forms\Components\Select::make('payment_method')
                            ->options(PaymentMethod::class)
                            ->required()
                            ->live()
                            ->prefixIcon('heroicon-m-banknotes')
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('client_payment_source_id', null)),

                        Forms\Components\Select::make('client_payment_source_id')
                            ->label('Payment Source')
                            ->placeholder('Select a payment source')
                            ->options(function (Get $get): array {
                                $clientId = self::resolveClientId($get);
                                $method = $get('payment_method');
                                if (!$clientId || !$method) {
                                    return [];
                                }

                                return ClientPaymentSource::query()
                                    ->where('client_id', $clientId)
                                    ->where('payment_method', $method)
                                    ->get()
                                    ->mapWithKeys(fn (ClientPaymentSource $s) => [
                                        $s->id => $s->displayLabel() . ($s->is_default ? ' ★' : ''),
                                    ])
                                    ->toArray();
                            })
                            ->required(fn (Get $get): bool => (bool) PaymentMethod::tryFrom($get('payment_method') ?? '')?->requiresSource())
                            ->visible(fn (Get $get): bool => (bool) PaymentMethod::tryFrom($get('payment_method') ?? '')?->requiresSource())
                            ->prefixIcon('heroicon-m-bookmark'),
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
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => $state === Payment::TYPE_SALES ? 'Sale' : 'Invoice')
                    ->color(fn(string $state): string => $state === Payment::TYPE_SALES ? 'success' : 'primary'),

                Tables\Columns\TextColumn::make('payable_reference')
                    ->label('Reference')
                    ->getStateUsing(function (Payment $record): string {
                        $payable = $record->payable;
                        if (!$payable) {
                            return '–';
                        }
                        return $payable instanceof Invoice
                            ? $payable->invoice_number
                            : ($payable->reference ?? $payable->sale_uuid);
                    })
                    ->searchable(false)
                    ->sortable(false)
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('amount')
                    ->money('GHS')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('payment_source')
                    ->label('Source')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('source_number')
                    ->label('Source No.')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->placeholder('—'),

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
                    ->options(PaymentMethod::class),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        Payment::TYPE_INVOICE => 'Invoice',
                        Payment::TYPE_SALES => 'Sale',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('receipt')
                        ->icon('heroicon-o-printer')
                        ->url(fn(Payment $record): string => URL::signedRoute('payments.receipt', $record))
                        ->openUrlInNewTab()
                        ->visible(fn(Payment $record): bool => $record->payable instanceof Invoice),
                    Tables\Actions\DeleteAction::make(),
                ])->icon('heroicon-m-ellipsis-vertical')->tooltip('Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(null);
    }

    public static function resolveClientId(Get $get): ?int
    {
        $type = $get('type');

        if ($type === Payment::TYPE_INVOICE) {
            $invoiceId = $get('invoice_id');
            return $invoiceId ? Invoice::find($invoiceId)?->client_id : null;
        }

        if ($type === Payment::TYPE_SALES) {
            $saleId = $get('sale_id');
            return $saleId ? Sale::find($saleId)?->client_id : null;
        }

        return null;
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
