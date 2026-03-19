<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function hasEmail(): bool
    {
        return !empty($this->email);
    }

    public function shouldBeBillAutomatically(): bool
    {
        return $this->auth_email !== null && $this->authemail !== '';
    }
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function paymentSources(): HasMany
    {
        return $this->hasMany(ClientPaymentSource::class);
    }

    /**
     * Payments made against this client's invoices or sales (via polymorphic payable).
     * Use Payment::whereHasMorph('payable', [Invoice::class], fn ($q) => $q->where('client_id', $id))
     *         ->orWhereHasMorph('payable', [Sale::class], fn ($q) => $q->where('client_id', $id))
     * for querying.
     */
    public function totalPaymentsAmount(): float
    {
        return (float) \App\Models\Payment::query()
            ->where(function ($q) {
                $q->whereHasMorph('payable', [\App\Models\Invoice::class], fn ($q2) => $q2->where('client_id', $this->getKey()))
                    ->orWhereHasMorph('payable', [\App\Models\Sale::class], fn ($q2) => $q2->where('client_id', $this->getKey()));
            })
            ->sum('amount');
    }

}
