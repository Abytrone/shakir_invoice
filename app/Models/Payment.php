<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Observers\PaymentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[ObservedBy(PaymentObserver::class)]
class Payment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'payment_method' => PaymentMethod::class,
    ];

    public const TYPE_INVOICE = 'invoice';
    public const TYPE_SALES = 'sales';

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function clientPaymentSource(): BelongsTo
    {
        return $this->belongsTo(ClientPaymentSource::class);
    }
}
