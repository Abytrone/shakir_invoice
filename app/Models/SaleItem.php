<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_id',
        'stock_id',
        'quantity',
        'unit_price',
        'discount',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    protected static function booted(): void
    {
        static::created(function (SaleItem $item): void {
            if ($item->stock_id) {
                $item->stock()->decrement('quantity', $item->quantity);
            }
        });

        static::updated(function (SaleItem $item): void {
            if ($item->stock_id) {
                $diff = $item->quantity - $item->getOriginal('quantity');
                if ($diff !== 0) {
                    $item->stock()->decrement('quantity', $diff);
                }
            }
        });

        static::deleted(function (SaleItem $item): void {
            if ($item->stock_id) {
                $item->stock()->increment('quantity', $item->quantity);
            }
        });

        static::restored(function (SaleItem $item): void {
            if ($item->stock_id) {
                $item->stock()->decrement('quantity', $item->quantity);
            }
        });
    }
}
