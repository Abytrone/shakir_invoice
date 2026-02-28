<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_uuid',
        'reference',
        'client_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (Sale $sale): void {
            if (empty($sale->sale_uuid)) {
                $sale->sale_uuid = (string) Str::uuid();
            }
            if (empty($sale->reference)) {
                $latest = static::withTrashed()->orderByDesc('id')->first();
                $nextNumber = $latest && preg_match('/^SAL(\d+)$/', $latest->reference, $m)
                    ? (int) $m[1] + 1
                    : 1;
                $sale->reference = 'SAL' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    protected function total(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                return (float) $this->saleItems->sum(fn (SaleItem $item): float => (float) ($item->quantity * $item->unit_price - $item->discount));
            },
        );
    }

    protected function amountPaid(): Attribute
    {
        return Attribute::make(
            get: fn (): float => (float) $this->payments->sum('amount'),
        );
    }
}
