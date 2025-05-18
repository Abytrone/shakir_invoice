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

    protected $fillable = [
        'client_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'grand_subtotal',
        'tax_rate',
        'tax_amount',
        'discount_rate',
        'discount_amount',
        'grand_total',
        'amount_paid',
        'balance',
        'status',
        'notes',
        'terms',
        'is_recurring',
        'recurring_frequency',
        'recurring_start_date',
        'recurring_end_date',
        'recurring_invoice_number_prefix',
    ];

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

        static::saving(function ($invoice) {
            $invoice->calculateTotals();
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

    public function calculateTotals(): void
    {
        // Calculate items subtotal
        $subtotal = $this->items->sum('total');
        $this->subtotal = $subtotal;

        // Calculate invoice-level tax
        $taxAmount = $subtotal * ($this->tax_rate / 100);
        $this->tax_amount = $taxAmount;

        // Calculate invoice-level discount
        $discountAmount = $subtotal * ($this->discount_rate / 100);
        $this->discount_amount = $discountAmount;

        // Calculate final total
        $this->grand_total = $subtotal + $taxAmount - $discountAmount;

        // Update balance
        $this->balance = $this->grand_total - $this->amount_paid;

        // Update status based on balance
        $this->updateStatus();
    }

    public function updateStatus(): void
    {
        if ($this->balance <= 0) {
            $this->status = 'paid';
        } elseif ($this->due_date < Carbon::today()) {
            $this->status = 'overdue';
        } elseif ($this->amount_paid > 0) {
            $this->status = 'partial';
        } elseif ($this->status === 'draft') {
            $this->status = 'sent';
        }
    }

    public function recordPayment(float $amount): void
    {
        $this->amount_paid += $amount;
        $this->balance = $this->total - $this->amount_paid;
        $this->updateStatus();
        $this->save();
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

    public function getNextRecurringDate(): ?Carbon
    {
        if (!$this->is_recurring || !$this->recurring_frequency) {
            return null;
        }

        $lastDate = $this->recurring_start_date;
        $now = Carbon::now();

        while ($lastDate <= $now) {
            switch ($this->recurring_frequency) {
                case 'daily':
                    $lastDate = $lastDate->addDay();
                    break;
                case 'weekly':
                    $lastDate = $lastDate->addWeek();
                    break;
                case 'monthly':
                    $lastDate = $lastDate->addMonth();
                    break;
                case 'yearly':
                    $lastDate = $lastDate->addYear();
                    break;
            }

            if ($this->recurring_end_date && $lastDate > $this->recurring_end_date) {
                return null;
            }
        }

        return $lastDate;
    }
}