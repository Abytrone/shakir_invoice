<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_uuid',
        'client_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (Sale $sale): void {
            if (empty($sale->sale_uuid)) {
                $sale->sale_uuid = (string) Str::uuid();
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
}
