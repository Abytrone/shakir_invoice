<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Mail\InvoiceSent;
use App\Constants\PaymentStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
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
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionAction(
                                fn(Forms\Components\Actions\Action $action) => $action->authorize('create', Client::class),
                            )
                            ->createOptionUsing(function (array $data): int {
                                Gate::authorize('create', Client::class);

                                return Client::create($data)->getKey();
                            })
                            ->label('Client')
                            ->helperText('Select the client for this invoice'),

                        Forms\Components\DatePicker::make('issue_date')
                            ->required()
                            ->default(now())
                            ->label('Issue Date')
                            ->helperText('The date when the invoice is issued')
                            ->maxDate(now()),
//                            ->live()
//                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
//                                if ($state) {
//                                    $set('due_date', \Carbon\Carbon::parse($state)->addDay()->format('Y-m-d'));
//                                }
//                            }),

                        Forms\Components\DatePicker::make('due_date')
                            ->required()
                            ->default(now()->addDay())
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
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name')
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\Textarea::make('description')
                                                    ->maxLength(65535)
                                                    ->columnSpanFull(),
                                                Forms\Components\TextInput::make('unit_price')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required()
                                                    ->prefix('GHS'),
                                            ])
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
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if ($state) {
                                                    $product = Product::find($state);
                                                    if ($product) {
                                                        $set('unit_price', $product->unit_price);
                                                    }
                                                }

                                                static::updateTotal($get, $set, prefix: '../../');
                                                static::syncDiscount($get, $set, prefix: '../../');
                                                static::syncTax($get, $set, prefix: '../../');
                                                static::updateTotal($get, $set, prefix: '../../');
                                            })
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make(name: 'quantity')
                                            ->integer()
                                            ->default(1)
                                            ->required()
                                            ->minValue(1)
                                            ->helperText('Number of units')
                                            ->live(onBlur: true)
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('unit_price')
                                            ->numeric()
                                            ->required()
                                            ->minValue(0)
                                            ->prefix('GHS')
                                            ->label('Unit Price')
                                            ->helperText('Price per unit')
                                            ->live(onBlur: true)
                                            ->columnSpan(1)
                                    ]),
                            ])
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->columnSpanFull()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                static::updateTotal($get, $set);
                                static::syncDiscount($get, $set);
                                static::syncTax($get, $set);
                                static::updateTotal($get, $set);
                            })
                            ->afterStateHydrated(function (Get $get, Set $set) {
                                static::updateTotal($get, $set);
                                static::syncDiscount($get, $set);
                                static::syncTax($get, $set);
                                static::updateTotal($get, $set);
                            }),
                    ]),

                Forms\Components\Section::make('Invoice Summary')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                // Tax Section
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Hidden::make('tax_type')->default('percent'),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('tax_rate')
                                                    ->label('Tax Rate')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->suffix('%')
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                                        $set('tax_type', 'percent');
                                                        self::syncTax($get, $set, changedField: 'tax_rate');
                                                        self::updateTotal($get, $set);
                                                    }),
                                                Forms\Components\TextInput::make('tax_amount')
                                                    ->label('Tax Amount')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->prefix('GHS')
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                                        $set('tax_type', 'fixed');
                                                        self::syncTax($get, $set, changedField: 'tax_amount');
                                                        self::updateTotal($get, $set);
                                                    }),
                                            ]),
                                    ])->columnSpan(1),

                                // Discount Section
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Hidden::make('discount_type')->default('percent'),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('discount_rate')
                                                    ->label('Discount Rate')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->suffix('%')
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                                        $set('discount_type', 'percent');
                                                        self::syncDiscount($get, $set, changedField: 'discount_rate');
                                                        self::updateTotal($get, $set);
                                                    }),
                                                Forms\Components\TextInput::make('discount_amount')
                                                    ->label('Discount Amount')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->prefix('GHS')
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                                        $set('discount_type', 'fixed');
                                                        self::syncDiscount($get, $set, changedField: 'discount_amount');
                                                        self::updateTotal($get, $set);
                                                    }),
                                            ]),
                                    ])->columnSpan(1),
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


    /**
     * Keeps discount_rate <-> discount_amount consistent against the current subtotal.
     *
     * @param  string  $prefix  State path prefix ('' for the outer form, '../../' from inside a repeater row).
     * @param  ?string  $changedField  Bare field name the user just edited ('discount_rate' or 'discount_amount'),
     *                                 left untouched so it isn't reformatted out from under the user on blur.
     */
    protected static function syncDiscount(Get $get, Set $set, string $prefix = '', ?string $changedField = null): void
    {
        $subtotal = (float)$get("{$prefix}subtotal");
        $discountType = $get("{$prefix}discount_type");
        $discountRate = (float)$get("{$prefix}discount_rate");
        $discountAmount = (float)$get("{$prefix}discount_amount");

        if ($discountType === 'fixed') {
            $discountRate = $subtotal > 0 ? ($discountAmount / $subtotal) * 100 : 0;
        } else {
            $discountAmount = $subtotal * ($discountRate / 100);
        }

        if ($changedField !== 'discount_rate') {
            $set("{$prefix}discount_rate", self::twoDpNumberFormat($discountRate));
        }
        if ($changedField !== 'discount_amount') {
            $set("{$prefix}discount_amount", self::twoDpNumberFormat($discountAmount));
        }
    }

    /**
     * Keeps tax_rate <-> tax_amount consistent against the current subtotal.
     *
     * @param  string  $prefix  State path prefix ('' for the outer form, '../../' from inside a repeater row).
     * @param  ?string  $changedField  Bare field name the user just edited ('tax_rate' or 'tax_amount'),
     *                                 left untouched so it isn't reformatted out from under the user on blur.
     */
    protected static function syncTax(Get $get, Set $set, string $prefix = '', ?string $changedField = null): void
    {
        $subtotal = (float)$get("{$prefix}subtotal");
        $taxType = $get("{$prefix}tax_type");
        $taxRate = (float)$get("{$prefix}tax_rate");
        $taxAmount = (float)$get("{$prefix}tax_amount");

        if ($taxType === 'fixed') {
            $taxRate = $subtotal > 0 ? ($taxAmount / $subtotal) * 100 : 0;
        } else {
            $taxAmount = $subtotal * ($taxRate / 100);
        }

        if ($changedField !== 'tax_rate') {
            $set("{$prefix}tax_rate", self::twoDpNumberFormat($taxRate));
        }
        if ($changedField !== 'tax_amount') {
            $set("{$prefix}tax_amount", self::twoDpNumberFormat($taxAmount));
        }
    }

    /**
     * Sums the line items into subtotal, then derives the grand total from the current
     * tax/discount amounts. Doesn't touch rate <-> amount conversion — see syncDiscount()/syncTax().
     *
     * @param  string  $prefix  State path prefix ('' for the outer form, '../../' from inside a repeater row).
     */
    protected static function updateTotal(Get $get, Set $set, string $prefix = ''): void
    {
        $subtotal = collect($get("{$prefix}items"))
            ->filter(fn($item) => !empty($item['product_id']) && !empty($item['quantity']))
            ->sum(fn($item) => (float)$item['unit_price'] * (int)$item['quantity']);

        $set("{$prefix}subtotal", self::twoDpNumberFormat($subtotal));

        $taxAmount = (float)$get("{$prefix}tax_amount");
        $discountAmount = (float)$get("{$prefix}discount_amount");

        $set("{$prefix}total", self::twoDpNumberFormat($subtotal + $taxAmount - $discountAmount));
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
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy(
                            Payment::query()
                                ->selectRaw('coalesce(sum(amount), 0)')
                                ->whereColumn('invoice_id', 'invoices.id')
                                ->where('status', PaymentStatus::COMPLETED),
                            $direction
                        );
                    })
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

                    Tables\Actions\Action::make('download_quotation')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn(Invoice $record): string => URL::signedRoute('invoices.download', [
                            'invoice' => $record,
                            'asQuotation' => true]))
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
                        ->label('Replicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('warning')
                        ->form([
                            \Filament\Forms\Components\DatePicker::make('issue_date')
                                ->label('New Issue Date')
                                ->default(now()->addMonth()->startOfMonth())
                                ->required(),
                            \Filament\Forms\Components\DatePicker::make('due_date')
                                ->label('New Due Date')
                                ->default(now()->addMonth()->startOfMonth()->addDays(30))
                                ->required(),
                            \Filament\Forms\Components\Toggle::make('is_recurring')
                                ->label('Make new invoice recurring?')
                                ->default(true),
                            \Filament\Forms\Components\Toggle::make('disable_old_recurring')
                                ->label('Turn off recurring on current invoice?')
                                ->default(true),
                        ])
                        ->action(function (Invoice $record, array $data) {
                            $newInvoice = $record->replicate();
                            $newInvoice->invoice_number = null; // Trigger observer to generate new number
                            $newInvoice->status = 'draft';
                            $newInvoice->issue_date = $data['issue_date'];
                            $newInvoice->due_date = $data['due_date'];
                            $newInvoice->is_recurring = $data['is_recurring'];
                            $newInvoice->save();

                            foreach ($record->items as $item) {
                                $newItem = $item->replicate();
                                $newItem->invoice_id = $newInvoice->id;
                                $newItem->save();
                            }

                            if ($data['disable_old_recurring']) {
                                $record->update(['is_recurring' => false]);
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Invoice Replicated')
                                ->body('New invoice created: ' . $newInvoice->invoice_number)
                                ->success()
                                ->send();
                        })
                        ->modalHeading('Replicate Invoice')
                        ->modalDescription('Set the dates and recurring options for the new invoice.'),

                    Tables\Actions\EditAction::make()->color('primary'),
                    Tables\Actions\DeleteAction::make(),
                ])->icon('heroicon-m-ellipsis-vertical')->tooltip('Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('generate_and_download_zip')
                        ->label('Generate Bills & Download ZIP')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('warning')
                        ->form([
                            \Filament\Forms\Components\DatePicker::make('issue_date')
                                ->label('New Issue Date')
                                ->default(now()->addMonth()->startOfMonth())
                                ->required(),
                            \Filament\Forms\Components\DatePicker::make('due_date')
                                ->label('New Due Date')
                                ->default(now()->addMonth()->startOfMonth()->addDays(30))
                                ->required(),
                            \Filament\Forms\Components\Toggle::make('is_recurring')
                                ->label('Make new invoices recurring?')
                                ->default(true),
                            \Filament\Forms\Components\Toggle::make('disable_old_recurring')
                                ->label('Turn off recurring on selected invoices?')
                                ->default(true),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            set_time_limit(300); // Increase time limit to 5 minutes for bulk PDF generation
                            $newInvoices = [];
                            foreach ($records as $record) {
                                $newInvoice = $record->replicate();
                                $newInvoice->invoice_number = null;
                                $newInvoice->status = 'draft';
                                $newInvoice->issue_date = $data['issue_date'];
                                $newInvoice->due_date = $data['due_date'];
                                $newInvoice->is_recurring = $data['is_recurring'];
                                $newInvoice->save();

                                foreach ($record->items as $item) {
                                    $newItem = $item->replicate();
                                    $newItem->invoice_id = $newInvoice->id;
                                    $newItem->save();
                                }

                                if ($data['disable_old_recurring']) {
                                    $record->update(['is_recurring' => false]);
                                }

                                $newInvoice->load('client', 'items.product');
                                $newInvoices[] = $newInvoice;
                            }

                            // Generate ZIP
                            $zipFileName = 'invoices_' . now()->format('Y_m_d_His') . '.zip';
                            $zipPath = storage_path('app/public/' . $zipFileName);

                            $zip = new \ZipArchive();
                            if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
                                foreach ($newInvoices as $invoice) {
                                    $containsProducts = $invoice->items->contains(function ($item) {
                                        return $item->product && $item->product->type === 'product';
                                    });

                                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.print', [
                                        'invoice' => $invoice,
                                        'client' => $invoice->client,
                                        'items' => $invoice->items,
                                        'containsProducts' => $containsProducts,
                                        'docType' => 'INVOICE'
                                    ]);

                                    $safeClientName = preg_replace('/[^A-Za-z0-9 _.-]/', '', $invoice->client->name);
                                    $zip->addFromString($safeClientName . '-' . $invoice->invoice_number . '.pdf', $pdf->output());
                                }
                                $zip->close();
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Invoices Generated')
                                ->body(count($newInvoices) . ' invoices have been generated and downloaded.')
                                ->success()
                                ->send();

                            return response()->download($zipPath)->deleteFileAfterSend(true);
                        })
                        ->deselectRecordsAfterCompletion()
                        ->modalHeading('Generate Next Month Bills & Download ZIP')
                        ->modalDescription('This will create new invoices for the selected records and download them all inside a ZIP file.'),
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

    /**
     * @param ?float $number
     * @return float
     */
    public static function twoDpNumberFormat(?float $number): float
    {
        return round($number ?? 0, 2);
    }
}
