<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $guarded = ['subtotal'];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
//            $item->calculateTotals();
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function calculateTotals(): void
    {
        // Calculate subtotal
        $this->subtotal = $this->quantity * $this->unit_price;

        // Calculate item-level tax
        $this->tax_amount = $this->subtotal * ($this->tax_rate / 100);

        // Calculate item-level discount
        $this->discount_amount = $this->subtotal * ($this->discount_rate / 100);

        // Calculate final total
        $this->total = $this->subtotal + $this->tax_amount - $this->discount_amount;
    }
}
