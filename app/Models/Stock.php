<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stock extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'unit_price' => 'decimal:2',
    ];

    protected function long_name(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => $value . "",
            set: fn($value) => $value,
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
