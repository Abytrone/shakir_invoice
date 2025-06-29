<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory, SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($quote) {
            if (! $quote->quote_number) {
                $latestQuote = static::withTrashed()->latest()->first();
                $nextNumber = $latestQuote ? intval(substr($latestQuote->quote_number, 3)) + 1 : 1;
                $quote->quote_number = 'QUO'.str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    protected $fillable = [
        'client_id',
        'quote_number',
        'issue_date',
        'expiry_date',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount_rate',
        'discount_amount',
        'total',
        'status',
        'notes',
        'terms',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }
}
