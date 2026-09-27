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
        'amount',
        'source',
        'digital_channel',
        'reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'withdrawal_date' => 'date',
            'amount' => 'decimal:2',
        ];
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
