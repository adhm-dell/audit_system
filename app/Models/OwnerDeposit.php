<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OwnerDeposit extends Model
{
    protected $fillable = [
        'owner_id',
        'cash_amount',
        'instapay_amount',
        'wallet_amount',
        'fawry_amount',
        'deposit_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'deposit_date' => 'date',
            'cash_amount' => 'decimal:2',
            'instapay_amount' => 'decimal:2',
            'wallet_amount' => 'decimal:2',
            'fawry_amount' => 'decimal:2',
        ];
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->cash_amount + $this->instapay_amount + $this->wallet_amount + $this->fawry_amount;
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }
}
