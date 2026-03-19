<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPaymentSource extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payment_method' => PaymentMethod::class,
        'is_default' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function displayLabel(): string
    {
        return "{$this->label} — {$this->source_number}";
    }
}
