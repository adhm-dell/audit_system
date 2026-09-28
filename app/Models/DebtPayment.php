<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DebtPayment extends Model
{
    protected $fillable = [
        'debt_id',
        'installment_id',
        'payment_date',
        'cash_amount',
        'instapay_amount',
        'wallet_amount',
        'fawry_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
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
    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(DebtInstallment::class, 'installment_id');
    }

    public function safeTransactions(): MorphMany
    {
        return $this->morphMany(SafeTransaction::class, 'reference');
    }
}
