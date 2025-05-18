<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company_name',
        'tax_number',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'notes',
    ];

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }
}
