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
        return $this->email !== null && $this->email !== '';
    }

    public function shouldBeBillAutomatically(): bool
    {
        return $this->auth_email !== null && $this->authemail !== '';
    }
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            \App\Models\Payment::class,
            \App\Models\Invoice::class,
            'client_id', // Foreign key on invoices table...
            'invoice_id', // Foreign key on payments table...
            'id', // Local key on clients table...
            'id'  // Local key on invoices table...
        );
    }
    public function hasEmail(): bool
    {
        return !empty($this->email);
    }
}
