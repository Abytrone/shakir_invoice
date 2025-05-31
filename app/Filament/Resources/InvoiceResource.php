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
                                            ->disableOptionWhen(function ($value, $state, Get $get) {
                                                return collect($get('../*.product_id'))
                                                    ->reject(fn($id) => $id == $state)
                                                    ->filter()
                                                    ->contains($value);
                                            })
                                            ->columnSpan(1),


                                        Forms\Components\TextInput::make(name: 'quantity')
                                            ->integer()
                                            ->default(1)
                                            ->required()
                                            ->minValue(1)
                                            ->helperText('Number of units')
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get) {
//                                                static::updateTotals($get, $set);
                                            })
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('unit_price')
                                            ->numeric()
                                            ->readonly()
                                            ->required()
                                            ->minValue(0)
                                            ->prefix('GHS')
                                            ->label('Unit Price')
                                            ->helperText('Price per unit')
                                            ->live()
                                            ->columnSpan(1),
                                    ])->afterStateUpdated(function ($state, Set $set, Get $get) {

                                        if ($state) {
                                            $product = Product::query()
                                                ->find($state['product_id']);

                                            if ($product) {

                                                $data = self::updateSingleProductTotals($product, $get('quantity'));
                                                $set('tax_amount', $data['tax_amount']);
                                                $set('discount_amount', $data['discount_amount']);

                                                $set('unit_price', $product->unit_price);
                                                $set('tax_rate', $product->tax_rate);
                                                $set('discount_rate', $product->discount_rate);
                                                $set('subtotal', $data['subtotal']);
                                                $set('total', $data['total']);
                                            }
                                        }
                                    })->afterStateHydrated(function ($state, Set $set, Get $get) {

                                        if ($state) {
                                            $product = Product::query()
                                                ->find($state['product_id']);

                                            if ($product) {

                                                $data = self::updateSingleProductTotals($product, $get('quantity'));
                                                $set('tax_amount', $data['tax_amount']);
                                                $set('discount_amount', $data['discount_amount']);

                                                $set('unit_price', $product->unit_price);
                                                $set('tax_rate', $product->tax_rate);
                                                $set('discount_rate', $product->discount_rate);
                                                $set('subtotal', $data['subtotal']);
                                                $set('total', $data['total']);
                                            }
                                        }
                                    }),

                                // Second row
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('tax_rate')
                                            ->numeric()
                                            ->readOnly()
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('%')
                                            ->label('Tax Rate')
                                            ->helperText('Tax rate percentage')
                                            ->live()
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
                                            ->readonly()
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('%')
                                            ->label('Discount Rate')
                                            ->helperText('Discount rate percentage')
                                            ->live()
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
                            ->live(),
                    ])->afterStateUpdated(function ($state, Get $get, Set $set) {
                        static::updateTotals($get, $set, $state);
                    })->afterStateHydrated(function ($state, Get $get, Set $set) {
                        static::updateTotals($get, $set, $state);
                    }),

                Forms\Components\Section::make('Invoice Summary')
                    ->schema([
                        // First row: Tax and Discount
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('total_tax_rate')
                                    ->numeric()
                                    ->disabled()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->label('Total Tax Rate')
                                    ->helperText('Overall tax rate for the invoice')
                                    ->live()->columnSpan(1),
//                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
//                                        static::updateTotals($get, $set);
//                                    }),

                                Forms\Components\TextInput::make('total_tax')
                                    ->numeric()
                                    ->disabled()
                                    ->prefix('GHS')
                                    ->label('Total Tax')
                                    ->helperText('Total tax amount for all items')
                                    ->live()
                                    ->afterStateHydrated(function (Get $get, Set $set) {
//                                        static::updateTotals($get, $set);
                                    })
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('total_discount_rate')
                                    ->numeric()
                                    ->disabled()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->label('Invoice Discount Rate')
                                    ->helperText('Overall discount rate for the invoice')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
//                                        static::updateTotals($get, $set);
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
//                                        static::updateTotals($get, $set);
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
//                                        static::updateTotals($get, $set);
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
//                                        static::updateTotals($get, $set);
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

    protected static function updateSingleProductTotals(Product $product, $quantity): array
    {
        $unitPrice = (float)($product->unit_price ?? 0);
        $taxRate = (float)($product->tax_rate ?? 0);
        $discountRate = (float)($product->discount_rate ?? 0);

        $subtotal = $quantity * $unitPrice;
        $taxAmount = $subtotal * ($taxRate / 100);
        $discountAmount = $subtotal * ($discountRate / 100);
        $total = $subtotal + $taxAmount - $discountAmount;
        return [
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'subtotal' => $subtotal,
            'total' => $total,
        ];
    }

    protected static function updateTotals(Get $get, Set $set): void
    {
        $selectedProducts = collect($get('items'))
            ->filter(fn($item) => !empty($item['product_id'])
                && !empty($item['quantity']));

        $totalTaxRate = 0;
        $totalTax = 0;

        $totalDiscountRate = 0;
        $totalDiscount = 0;

        $subtotal = 0;

        $products = Product::query()
            ->find($selectedProducts->pluck('product_id'));


        foreach ($products as $item) {
            $qty = $selectedProducts->pluck('quantity', 'product_id');
            $singleProductTotals = self::updateSingleProductTotals($item, $qty[$item->id]);

            $totalTaxRate += $item->tax_rate;
            $totalTax += $singleProductTotals['tax_amount'];

            $totalDiscountRate += $item->discount_rate;
            $totalDiscount += $singleProductTotals['discount_amount'];

            $subtotal += $singleProductTotals['subtotal'];
        }

        $grandTotal = $subtotal + $totalTax - $totalDiscount;

        $set('total_tax_rate', $totalTaxRate);
        $set('total_tax', $totalTax);
        $set('grand_subtotal', $subtotal);
        $set('total_discount', $totalDiscount);
        $set('total_discount_rate', $totalDiscountRate);
        $set('grand_total', $grandTotal);

        $amountPaid = (float)($get('amount_paid') ?? 0);
        $balance = $grandTotal - $amountPaid;
        $set('balance', $balance);

    }

    protected static function updateTotalsOld(Get $get, Set $set, $state): void
    {
        $items = collect($get('items'))
            ->filter(fn($item) => !empty($item['product_id']) && !empty($item['quantity']));

        $totalTaxRate = 0;
        $totalTax = 0;
        $totalDiscountRate = 0;
        $totalDiscount = 0;

        $subtotal = 0;
        $grandTotal = 0;

        // First calculate item-level totals
        foreach ($items as $key => $item) {
            $product = Product::query()
                ->find($item['product_id']);
            if (!$product) continue;
            $unitPrice = $product->unit_price;

            $taxRate = $product->tax_rate;
            $totalTaxRate += $taxRate;

            $totalTax += $product->tax_amount;

            $discountRate = $product->discount_rate;
            $totalDiscountRate += $discountRate;

            $totalDiscount += $product->discount_amount;


            $quantity = $item['quantity'];
            $subtotal += $quantity * $unitPrice;
            $grandTotal += $subtotal - $product->discount_amount + $product->tax_amount;

        }

        $set('total_tax_rate', $totalTaxRate);
        $set('total_tax', $totalTax);
        $set('grand_subtotal', $subtotal);
        $set('total_discount', $totalDiscount);
        $set('total_discount_rate', $totalDiscountRate);
        $set('grand_total', $grandTotal);

        // Update balance
        $amountPaid = (float)($get('amount_paid') ?? 0);
        $balance = $grandTotal - $amountPaid;
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
