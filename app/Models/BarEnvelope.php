<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarEnvelope extends Model
{
    protected $fillable = [
        'bar_date',
        'cash_total',
        'network_instapay_total',
        'network_wallet_total',
        'network_fawry_total',
        'network_total',
        'debts_total',
        'debt_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'bar_date' => 'date',
            'cash_total' => 'decimal:2',
            'network_instapay_total' => 'decimal:2',
            'network_wallet_total' => 'decimal:2',
            'network_fawry_total' => 'decimal:2',
            'network_total' => 'decimal:2',
            'debts_total' => 'decimal:2',
        ];
    }

    protected static function booted()
    {
        static::saving(function (BarEnvelope $envelope) {
            $envelope->network_total = $envelope->network_instapay_total + $envelope->network_wallet_total + $envelope->network_fawry_total;
        });
    }

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class, 'debt_id');
    }
}
