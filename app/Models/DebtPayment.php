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
        'amount',
        'source',
        'digital_channel',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
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
