<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class OwnerWithdrawal extends Model
{
    protected $fillable = [
        'owner_id',
        'withdrawal_date',
        'cash_amount',
        'instapay_amount',
        'wallet_amount',
        'fawry_amount',
        'reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'withdrawal_date' => 'date',
            'cash_amount' => 'decimal:2',
            'instapay_amount' => 'decimal:2',
            'wallet_amount' => 'decimal:2',
            'fawry_amount' => 'decimal:2',
        ];
    }

    public function getTotalAmountAttribute(): float
    {
        return ($this->cash_amount ?? 0) + ($this->instapay_amount ?? 0) + ($this->wallet_amount ?? 0) + ($this->fawry_amount ?? 0);
    }

    // ── Relations ──
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function safeTransactions(): MorphMany
    {
        return $this->morphMany(SafeTransaction::class, 'reference');
    }
}
