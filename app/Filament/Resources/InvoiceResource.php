<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Mail\InvoiceSent;
use App\Models\Invoice;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

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
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Invoice Number')
                            ->disabled()
                            ->visible(fn(string $context): bool => $context === 'edit')
                            ->dehydrated(false) // Don't try to save this field
                            ->columnSpanFull(),

                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'name')
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
                            ->reactive(),
                    ])->columns(4),

                Forms\Components\Section::make('Invoice Items')
                    ->icon('heroicon-o-shopping-cart')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                // First row
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Select::make('product_id')
                                            ->relationship('product', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->label('Product')
                                            ->helperText('Select a product to add to the invoice')
                                            ->live()
                                            ->getOptionLabelFromRecordUsing(fn(Product $record) => $record->name)
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
                                    ])->afterStateUpdated(self::setProductPricingDetails())
                                    ->afterStateHydrated(self::setProductPricingDetails()),
                            ])
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->columnSpanFull()
                            ->live(),
                    ])->afterStateUpdated(function (Get $get, Set $set) {
                        static::updateTotals($get, $set);
                    })->afterStateHydrated(function (Get $get, Set $set) {
                        static::updateTotals($get, $set);
                    }),

                Forms\Components\Section::make('Invoice Summary')
                    ->icon('heroicon-o-currency-dollar')
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
                                    ->helperText('Overall tax rate for the invoice')
                                    ->live()->columnSpan(1),

                                Forms\Components\TextInput::make('tax_amount')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->disabled()
                                    ->prefix('GHS')
                                    ->label('Total Tax')
                                    ->helperText('Total tax amount for all items')
                                    ->live()
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
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('discount_amount')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->disabled()
                                    ->prefix('GHS')
                                    ->label('Total Discount')
                                    ->helperText('Total discount amount for all items')
                                    ->live()
                                    ->columnSpan(1),
                            ]),

                        // Second row: Totals
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('subtotal')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->disabled()
                                    ->live()
                                    ->prefix('GHS')
                                    ->label('Subtotal')
                                    ->helperText('Total before tax and discount')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('total')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->disabled()
                                    ->live()
                                    ->prefix('GHS')
                                    ->label('Grand Total')
                                    ->helperText('Final invoice amount')
                                    ->columnSpan(1),
                            ]),
                    ]),

                Forms\Components\Section::make('Additional Information')
                    ->icon('heroicon-o-document-text')
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
                    ->icon('heroicon-o-arrow-path')
                    ->schema([
                        Forms\Components\Toggle::make('is_recurring')
                            ->label('Enable Recurring Invoice')
                            ->helperText('Set up automatic invoice generation')
                            ->reactive(),

                        Forms\Components\Select::make('recurring_frequency')
                            ->options([
                                'monthly' => 'Monthly',
                                'yearly' => 'Yearly',
                            ])
                            ->visible(fn(Get $get) => $get('is_recurring'))
                            ->required(fn(Get $get) => $get('is_recurring'))
                            ->label('Frequency')
                            ->helperText('How often should the invoice be generated'),

                    ])->columns(),
            ]);
    }

    protected static function updateSingleProductTotals(Product $product, $quantity): array
    {
        $unitPrice = (float) ($product->unit_price ?? 0);
        $subtotal = $quantity * $unitPrice;

        return [
            'subtotal' => $subtotal,
        ];
    }

    protected static function updateTotals(Get $get, Set $set): void
    {
        $selectedProducts = collect($get('items'))
            ->filter(fn($item) => !empty($item['product_id'])
                && !empty($item['quantity']));

        $subtotal = 0;

        $products = Product::query()
            ->find($selectedProducts->pluck('product_id'));

        foreach ($products as $item) {
            $qty = $selectedProducts->pluck('quantity', 'product_id');
            $singleProductTotals = self::updateSingleProductTotals($item, $qty[$item->id]);

            $subtotal += $singleProductTotals['subtotal'];
        }


        $tax_rate = (float) $get('tax_rate');
        $taxAmount = $subtotal * (($tax_rate) / 100);

        $discount_rate = (float) $get('discount_rate');
        $discountAmount = $subtotal * (($discount_rate) / 100);

        $grandTotal = $subtotal + $taxAmount - $discountAmount;
        // Log::info('', [round($subtotal, 2), round($grandTotal, 2)]);

        $set('tax_amount', round($taxAmount, 2));
        $set('discount_amount', round($discountAmount, 2));
        $set('subtotal', round($subtotal, 2));
        $set('total', round($grandTotal, 2));

    }

    public static function setProductPricingDetails(): \Closure
    {
        return function ($state, Set $set, Get $get) {

            if ($state) {
                $product = Product::query()
                    ->find($state['product_id']);

                if ($product) {
                    $set('unit_price', $product->unit_price);
                }
            }
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->copyable(),

                Tables\Columns\TextColumn::make('client.name')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Invoice $record) => $record->client->email)
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('issue_date')
                    ->date('M d, Y')
                    ->sortable()
                    ->icon('heroicon-m-calendar'),

                Tables\Columns\TextColumn::make('due_date')
                    ->date('M d, Y')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->color(fn(Invoice $record) => $record->due_date < now() && $record->status !== 'paid' ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->sortable()
                    ->money('GHS')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->sortable()
                    ->money('GHS')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->icon(fn(string $state): string => match ($state) {
                        'draft' => 'heroicon-m-pencil-square',
                        'sent' => 'heroicon-m-paper-airplane',
                        'paid' => 'heroicon-m-check-circle',
                        'overdue' => 'heroicon-m-exclamation-circle',
                        'partial' => 'heroicon-m-banknotes',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'info',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'partial' => 'warning',
                    }),

                Tables\Columns\IconColumn::make('is_recurring')
                    ->boolean()
                    ->label('Recurring')
                    ->trueIcon('heroicon-o-arrow-path')
                    ->falseIcon('heroicon-o-x-mark'),
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
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('Created From'),
                        Forms\Components\DatePicker::make('created_until')->label('Created Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                Tables\Filters\Filter::make('is_recurring')
                    ->query(fn(Builder $query): Builder => $query->where('is_recurring', true))
                    ->label('Recurring Only'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('print')
                        ->icon('heroicon-o-printer')
                        ->url(fn(Invoice $record): string => route('invoices.print', $record))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('download')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn(Invoice $record): string => URL::signedRoute('invoices.download', $record))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('send')
                        ->label(fn(Invoice $record) => $record->status == 'draft' ? 'Send' : 'Resend')
                        ->icon('heroicon-o-paper-airplane')
                        ->action(function (Invoice $record) {
                            $record->update(['status' => 'sent']);

                            if ($record->client->hasEmail()) {
                                Mail::to($record->client->email)
                                    ->send(new InvoiceSent($record));
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Invoice Sent')
                                ->success()
                                ->send();

                        })
                        ->requiresConfirmation(),

                    Tables\Actions\Action::make('replicate')
                        ->label('Replicate Next Month')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('warning')
                        ->action(function (Invoice $record) {
                            $newInvoice = $record->replicate();
                            $newInvoice->invoice_number = null; // Trigger observer to generate new number
                            $newInvoice->status = 'draft';
                            $newInvoice->issue_date = $record->issue_date ? $record->issue_date->addMonth() : now();
                            $newInvoice->due_date = $record->due_date ? $record->due_date->addMonth() : now()->addDays(30);
                            $newInvoice->is_recurring = false; // Manual replication, so disable auto-recurring on the copy
                            $newInvoice->save();

                            foreach ($record->items as $item) {
                                $newItem = $item->replicate();
                                $newItem->invoice_id = $newInvoice->id;
                                $newItem->save();
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Invoice Replicated')
                                ->body('New invoice created for next month: ' . $newInvoice->invoice_number)
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Replicate Invoice for Next Month')
                        ->modalDescription('This will create a draft invoice for the next month with the same items. Are you sure?'),

                    Tables\Actions\EditAction::make()->color('primary'),
                    Tables\Actions\DeleteAction::make(),
                ])->icon('heroicon-m-ellipsis-vertical')->tooltip('Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('replicate_bulk')
                        ->label('Generate Next Month Bills')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('warning')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                $newInvoice = $record->replicate();
                                $newInvoice->invoice_number = null;
                                $newInvoice->status = 'draft';
                                $newInvoice->issue_date = $record->issue_date ? $record->issue_date->addMonth() : now();
                                $newInvoice->due_date = $record->due_date ? $record->due_date->addMonth() : now()->addDays(30);
                                $newInvoice->is_recurring = false;
                                $newInvoice->save();

                                foreach ($record->items as $item) {
                                    $newItem = $item->replicate();
                                    $newItem->invoice_id = $newInvoice->id;
                                    $newItem->save();
                                }
                                $count++;
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Bulk Replication Complete')
                                ->body("$count invoices have been generated for the upcoming month.")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Generate Next Month Bills for Selected')
                        ->modalDescription('This will create new draft invoices for all selected records, set to next month dates. Proceed?')
                        ->deselectRecordsAfterCompletion(),
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
