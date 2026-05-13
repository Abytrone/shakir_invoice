<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receipt extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'client_id' => 'integer',
        'receipt_date' => 'datetime',
        'items' => 'array',
        'subtotal' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    protected function clientName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->received_from_name,
        );
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Receipt $receipt) {
            if ($receipt->receipt_number) {
                return;
            }

            $latestReceipt = static::withTrashed()->orderByDesc('id')->first();
            $nextNumber = $latestReceipt ? intval(substr($latestReceipt->receipt_number, 3)) + 1 : 1;

            $receipt->receipt_number = 'RCP' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        });
    }
}
