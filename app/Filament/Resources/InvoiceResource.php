<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Billing';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'company_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Client')
                            ->helperText('Select the client for this invoice'),

                        Forms\Components\DatePicker::make('issue_date')
                            ->required()
                            ->default(now())
                            ->label('Issue Date')
                            ->helperText('The date when the invoice is issued')
                            ->maxDate(now()),

                        Forms\Components\DatePicker::make('due_date')
                            ->required()
                            ->default(now()->addDays(30))
                            ->label('Due Date')
                            ->helperText('The date when the invoice payment is due')
                            ->minDate(fn(callable $get) => $get('issue_date')),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'sent' => 'Sent',
                                'paid' => 'Paid',
                                'overdue' => 'Overdue',
                                'partial' => 'Partial',
                            ])
                            ->default('draft')
                            ->required()
                            ->label('Status')
                            ->helperText('Current status of the invoice')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'paid') {
                                    $set('amount_paid', $set('total'));
                                }
                            }),
                    ])->columns(4),

                Forms\Components\Section::make('Invoice Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                // First row
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\Select::make('product_id')
                                            ->relationship('product', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->label('Product')
                                            ->helperText('Select a product to add to the invoice')
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if ($state) {
                                                    $product = Product::find($state);
                                                    if ($product) {
                                                        $set('description', $product->description);
                                                        $set('unit_price', $product->unit_price);
                                                        $set('tax_rate', $product->tax_rate);
                                                        $set('discount_rate', $product->discount_rate ?? 0);

                                                        $set('subtotal', $product->unit_price);
                                                        $set('total', $product->unit_price);
                                                        static::updateTotals($get, $set);
                                                    }
                                                }
                                            })
                                            ->disableOptionWhen(function ($value, $state, Get $get) {
                                                return collect($get('../*.product_id'))
                                                    ->reject(fn($id) => $id == $state)
                                                    ->filter()
                                                    ->contains($value);
                                            })
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('description')
                                            ->required()
                                            ->helperText('Product description')
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make(name: 'quantity')
                                            ->integer()
                                            ->default(1)
                                            ->required()
                                            ->minValue(1)
                                            ->helperText('Number of units')
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                static::updateTotals($get, $set);
                                            })
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('unit_price')
                                            ->numeric()
                                            ->required()
                                            ->minValue(0)
                                            ->prefix('GHS')
                                            ->label('Unit Price')
                                            ->helperText('Price per unit')
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                static::updateTotals($get, $set);
                                            })
                                            ->columnSpan(1),
                                    ]),

                                // Second row
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('tax_rate')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('%')
                                            ->label('Tax Rate')
                                            ->helperText('Tax rate percentage')
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                static::updateTotals($get, $set);
                                            })
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('tax_amount')
                                            ->numeric()
                                            ->disabled()
                                            ->prefix('GHS')
                                            ->label('Tax Amount')
                                            ->helperText('Calculated tax amount')
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('discount_rate')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('%')
                                            ->label('Discount Rate')
                                            ->helperText('Discount rate percentage')
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                static::updateTotals($get, $set);
                                            })
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('discount_amount')
                                            ->numeric()
                                            ->disabled()
                                            ->prefix('GHS')
                                            ->label('Discount Amount')
                                            ->helperText('Calculated discount amount')
                                            ->columnSpan(1),
                                    ]),

                                // Total row
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('subtotal')
                                            ->numeric()
                                            ->disabled()
                                            ->live()
                                            ->prefix('GHS')
                                            ->label('Subtotal')
                                            ->helperText('Total before tax and discount')
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('total')
                                            ->numeric()
                                            ->disabled()
                                            ->live()
                                            ->prefix('GHS')
                                            ->label('Total')
                                            ->helperText('Final amount for this item')
                                            ->columnSpan(2),
                                    ]),
                            ])
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->columnSpanFull()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                static::updateTotals($get, $set);
                            }),
                    ]),

                Forms\Components\Section::make('Invoice Summary')
                    ->schema([
                        // First row: Tax and Discount
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('tax_rate')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->label('Invoice Tax Rate')
                                    ->helperText('Overall tax rate for the invoice')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        static::updateTotals($get, $set);
                                    })
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('total_tax')
                                    ->numeric()
                                    ->disabled()
                                    ->prefix('GHS')
                                    ->label('Total Tax')
                                    ->helperText('Total tax amount for all items')
                                    ->live()
                                    ->afterStateHydrated(function (Get $get, Set $set) {
                                        static::updateTotals($get, $set);
                                    })
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('discount_rate')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->label('Invoice Discount Rate')
                                    ->helperText('Overall discount rate for the invoice')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        static::updateTotals($get, $set);
                                    })
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('total_discount')
                                    ->numeric()
                                    ->disabled()
                                    ->prefix('GHS')
                                    ->label('Total Discount')
                                    ->helperText('Total discount amount for all items')
                                    ->live()
                                    ->afterStateHydrated(function (Get $get, Set $set) {
                                        static::updateTotals($get, $set);
                                    })
                                    ->columnSpan(1),
                            ]),

                        // Second row: Totals
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('grand_subtotal')
                                    ->numeric()
                                    ->disabled()
                                    ->live()
                                    ->prefix('GHS')
                                    ->label('Subtotal')
                                    ->helperText('Total before tax and discount')
                                    ->afterStateHydrated(function (Get $get, Set $set) {
                                        static::updateTotals($get, $set);
                                    })
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('grand_total')
                                    ->numeric()
                                    ->disabled()
                                    ->live()
                                    ->prefix('GHS')
                                    ->label('Grand Total')
                                    ->helperText('Final invoice amount')
                                    ->afterStateHydrated(function (Get $get, Set $set) {
                                        static::updateTotals($get, $set);
                                    })
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('amount_paid')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefix('GHS')
                                    ->label('Amount Paid')
                                    ->helperText('Amount received from client')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $total = $get('grand_total') ?? 0;
                                        $set('balance', $total - $state);

                                        // Update status based on payment
                                        if ($state >= $total) {
                                            $set('status', 'paid');
                                        } elseif ($state > 0) {
                                            $set('status', 'partial');
                                        }
                                    })
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('balance')
                                    ->numeric()
                                    ->disabled()
                                    ->prefix('GHS')
                                    ->label('Balance')
                                    ->helperText('Remaining amount to be paid')
                                    ->columnSpan(1),
                            ]),
                    ]),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->label('Notes')
                            ->helperText('Additional notes for the client')
                            ->placeholder('Enter any additional notes here...'),

                        Forms\Components\Textarea::make('terms')
                            ->rows(3)
                            ->label('Terms & Conditions')
                            ->helperText('Payment terms and conditions')
                            ->placeholder('Enter payment terms and conditions here...'),
                    ])->columns(2),

                Forms\Components\Section::make('Recurring Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_recurring')
                            ->label('Enable Recurring Invoice')
                            ->helperText('Set up automatic invoice generation')
                            ->reactive(),

                        Forms\Components\Select::make('recurring_frequency')
                            ->options([
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                                'yearly' => 'Yearly',
                            ])
                            ->visible(fn(Get $get) => $get('is_recurring'))
                            ->required(fn(Get $get) => $get('is_recurring'))
                            ->label('Frequency')
                            ->helperText('How often should the invoice be generated'),

                        Forms\Components\DatePicker::make('recurring_start_date')
                            ->visible(fn(Get $get) => $get('is_recurring'))
                            ->required(fn(Get $get) => $get('is_recurring'))
                            ->label('Start Date')
                            ->helperText('When to start generating recurring invoices')
                            ->minDate(now()),

                        Forms\Components\DatePicker::make('recurring_end_date')
                            ->visible(fn(Get $get) => $get('is_recurring'))
                            ->label('End Date')
                            ->helperText('When to stop generating recurring invoices (optional)')
                            ->minDate(fn(Get $get) => $get('recurring_start_date')),

                        Forms\Components\TextInput::make('recurring_invoice_number_prefix')
                            ->visible(fn(Get $get) => $get('is_recurring'))
                            ->label('Invoice Number Prefix')
                            ->helperText('Prefix to identify recurring invoices (e.g., REC-)')
                            ->placeholder('REC-'),
                    ])->columns(2),
            ]);
    }

    protected static function updateTotals(Get $get, Set $set): void
    {
        $items = collect($get('items'))
            ->filter(fn($item) => !empty($item['product_id']) && !empty($item['quantity']));

        $subtotal = 0;
        $totalTax = 0;
        $totalDiscount = 0;

        // First calculate item-level totals
        foreach ($items as $key => $item) {
            $product = Product::find($item['product_id']);
            if (!$product) continue;

            // info($item['quantity']);

            $quantity = $item['quantity'];
            $unitPrice = (float)($item['unit_price'] ?? $product->unit_price ?? 0);
            $taxRate = (float)($item['tax_rate'] ?? $product->tax_rate ?? 0);
            $discountRate = (float)($item['discount_rate'] ?? $product->discount_rate ?? 0);

            $itemSubtotal = $quantity * $unitPrice;
            $itemTaxAmount = $itemSubtotal * ($taxRate / 100);
            $itemDiscountAmount = $itemSubtotal * ($discountRate / 100);
            $itemTotal = $itemSubtotal + $itemTaxAmount - $itemDiscountAmount;

            $subtotal += $itemSubtotal;
            $totalTax += $itemTaxAmount;
            $totalDiscount += $itemDiscountAmount;

            // Update individual item calculations with formatted display values
            $set("items.{$key}.subtotal", $itemSubtotal);
            $set("items.{$key}.tax_amount", $itemTaxAmount);
            $set("items.{$key}.discount_amount", $itemDiscountAmount);
            $set("items.{$key}.total", $itemTotal);
        }

        // Now apply invoice-level tax and discount
        $invoiceTaxRate = (float)($get('tax_rate') ?? 0);
        $invoiceDiscountRate = (float)($get('discount_rate') ?? 0);

        // Calculate invoice-level tax and discount on the subtotal
        $invoiceTaxAmount = $subtotal * ($invoiceTaxRate / 100);
        $invoiceDiscountAmount = $subtotal * ($invoiceDiscountRate / 100);

        // Add invoice-level tax and discount to the totals
        $totalTax += $invoiceTaxAmount;
        $totalDiscount += $invoiceDiscountAmount;

        // Calculate final total
        $total = $subtotal + $totalTax - $totalDiscount;

        // Update invoice summary with formatted display values
        $set('grand_subtotal', $subtotal);
        $set('total_tax', $totalTax);
        $set('total_discount', $totalDiscount);
        $set('grand_total', $total);

        // Update balance
        $amountPaid = (float)($get('amount_paid') ?? 0);
        $balance = $total - $amountPaid;
        $set('balance', $balance);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('client.company_name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('grand_total')
                    ->money('GHS')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'info',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'partial' => 'warning',
                    }),

                Tables\Columns\IconColumn::make('is_recurring')
                    ->boolean()
                    ->label('Recurring'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'partial' => 'Partial',
                    ]),
                Tables\Filters\Filter::make('is_recurring')
                    ->query(fn(Builder $query): Builder => $query->where('is_recurring', true)),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye'),

                    Tables\Actions\Action::make('print')
                        ->icon('heroicon-o-printer')
                        ->url(fn(Invoice $record): string => route('invoices.print', $record))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('download')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn(Invoice $record): string => route('invoices.download', $record))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('send')
                        ->icon('heroicon-o-paper-airplane')
                        ->action(fn(Invoice $record) => $record->update(['status' => 'sent']))
                        ->requiresConfirmation(),

                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}