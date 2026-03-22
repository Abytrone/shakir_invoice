<?php

namespace App\Models;

use App\Enums\AdjustmentReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockAdjustment extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'reason' => AdjustmentReason::class,
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::created(function (StockAdjustment $adjustment): void {
            $adjustment->stock()->decrement('quantity', $adjustment->quantity);
        });

        static::deleted(function (StockAdjustment $adjustment): void {
            $adjustment->stock()->increment('quantity', $adjustment->quantity);
        });

        static::restored(function (StockAdjustment $adjustment): void {
            $adjustment->stock()->decrement('quantity', $adjustment->quantity);
        });
    }
}
