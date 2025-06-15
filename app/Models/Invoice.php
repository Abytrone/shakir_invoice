<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'recurring_start_date' => 'date',
        'is_recurring' => 'boolean',
        'tax_rate' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'has_next' => 'boolean',
    ];


    public function markAsPaid(): void
    {
        if ($this->status != 'paid')
            $this->update(['status' => 'paid']);
    }

    public function markAsOverdue(): void
    {
        $this->update(['status' => 'overdue']);
    }

    public function markAsSent(): void
    {
        if ($this->status != 'sent')
            $this->update(['status' => 'sent']);
    }

    public function markAsDraft(): void
    {
        if ($this->status != 'draft')
            $this->update(['status' => 'draft']);
    }

    public function markAsCancelled(): void
    {
        if ($this->status != 'cancelled')
            $this->update(['status' => 'cancelled']);
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
                $itemsSum = $this->items->sum('total');

                $taxAmount = $attributes['tax_rate'] / 100 * $itemsSum;

                $discountAmount = $attributes['discount_rate'] / 100 * $itemsSum;

                $result = $itemsSum + $taxAmount - $discountAmount;

                return round($result, 2);
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


    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isOverdue(): bool
    {
        return $this->due_date < Carbon::today();
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPaid(): bool
    {
        return $this->payments->sum('amount') >= $this->total;
    }

    public function isPartial(): bool
    {
        return $this->payments->sum('amount') > 0 && $this->balance > 0;
    }

    protected function amountPaid(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => $this->payments->sum('amount'),
            set: fn($value) => $value,
        );
    }

    protected function amountToPay(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => round($this->total - $this->payments->sum('amount'), 2),
            set: fn($value) => $value,
        );
    }

    /**
     * @param $invoice
     * @return void
     */
    public static function setRecurringEndDate($invoice): void
    {
        if ($invoice->is_recurring) {
//            $days = ['daily' => 1, 'weekly' => 7, 'monthly' => 30, 'yearly' => 365][$invoice->recurring_frequency];
            $invoice->next_recurring_date = $invoice->due_date;//->addDays($days);
        }
    }

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

}
