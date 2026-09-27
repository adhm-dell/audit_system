<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DebtInstallment extends Model
{
    protected $fillable = [
        'debt_id',
        'installment_number',
        'due_date',
        'amount',
        'status',
        'paid_amount',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    // ── Helpers ──
    public function getRemainingAmountAttribute(): float
    {
        return $this->amount - $this->paid_amount;
    }

    // ── Relations ──
    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DebtPayment::class, 'installment_id');
    }
}
