<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
            if (!$invoice->invoice_number) {
                $latestInvoice = static::withTrashed()->latest()->first();
                $nextNumber = $latestInvoice ? intval(substr($latestInvoice->invoice_number, 3)) + 1 : 1;
                $invoice->invoice_number = 'INV' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            }
        });

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

}
