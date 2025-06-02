<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Invoice extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'recurring_start_date' => 'date',
        'recurring_end_date' => 'date',
        'is_recurring' => 'boolean',
        'grand_subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            $latestInvoice = static::withTrashed()->latest()->first();
            $nextNumber = $latestInvoice ? intval(substr($latestInvoice->invoice_number, 3)) + 1 : 1;
            $invoice->invoice_number = 'INV' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            self::setRecurringEndDate($invoice);
        });

        static::updating(function ($invoice) {
            self::setRecurringEndDate($invoice);
        });

    }

    protected function subtotal(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                return $this->items->sum('total');
            },
            set: fn($value) => $value,
        );
    }

    protected function discount(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                return $this->items->sum('total') * ($attributes['discount_rate'] / 100);
            },
            set: fn($value) => $value,
        );
    }
    protected function tax(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                return $this->items->sum('total') * ($attributes['tax_rate'] / 100);
            },
            set: fn($value) => $value,
        );
    }

    protected function total(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                $taxAmount = $attributes['tax_rate'] / 100 * $this->items->sum('total');

                $discountAmount = $attributes['discount_rate'] / 100 * $this->items->sum('total');

                return $this->items->sum('total') + $taxAmount - $discountAmount;
            },
            set: fn($value) => $value,
        );
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }


    public function isOverdue(): bool
    {
        return $this->due_date < Carbon::today() && $this->balance > 0;
    }

    public function isPaid(): bool
    {
        return $this->balance <= 0;
    }

    public function isPartial(): bool
    {
        return $this->amount_paid > 0 && $this->balance > 0;
    }

    /**
     * @param $invoice
     * @return void
     */
    public static function setRecurringEndDate($invoice): void
    {
        if ($invoice->is_recurring) {
            $days = ['daily' => 1, 'weekly' => 7, 'monthly' => 30, 'yearly' => 365][$invoice->recurring_frequency];
            $invoice->next_recurring_date = $invoice->due_date->addDays($days);
        }
    }

}
