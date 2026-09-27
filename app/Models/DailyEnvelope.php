<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyEnvelope extends Model
{
    protected $fillable = [
        'envelope_date',
        'cash_total',
        'network_instapay_total',
        'network_wallet_total',
        'network_fawry_total',
        'network_total',
        'digital_channel',
        'expenses_total',
        'expenses_paid_from',
        'expenses_already_deducted',
        'net_cash_to_safe',
        'net_digital',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'envelope_date' => 'date',
            'cash_total' => 'decimal:2',
            'network_instapay_total' => 'decimal:2',
            'network_wallet_total' => 'decimal:2',
            'network_fawry_total' => 'decimal:2',
            'network_total' => 'decimal:2',
            'expenses_total' => 'decimal:2',
            'net_cash_to_safe' => 'decimal:2',
            'net_digital' => 'decimal:2',
            'expenses_already_deducted' => 'boolean',
        ];
    }

    // ── Boot: auto-compute net values ──
    protected static function booted(): void
    {
        static::saving(function (DailyEnvelope $envelope) {
            $envelope->network_total = $envelope->network_instapay_total + $envelope->network_wallet_total + $envelope->network_fawry_total;
            // Removed expenses_total sum here, we handle it via observer or manually.
            $envelope->computeNetValues();
        });
    }

    public function recalculate(): void
    {
        $this->expenses_total = $this->items()->sum('amount');
        $this->computeNetValues();
        $this->saveQuietly();
        
        // Let observer handle recreating the transactions
        event('eloquent.saved: App\Models\DailyEnvelope', $this);
    }

    public function computeNetValues(): void
    {
        if ($this->expenses_already_deducted) {
            // Expenses already deducted from the offline POS totals
            $this->net_cash_to_safe = $this->cash_total;
            $this->net_digital = $this->network_total;
        } else {
            if ($this->expenses_paid_from === 'cash') {
                $this->net_cash_to_safe = $this->cash_total - $this->expenses_total;
                $this->net_digital = $this->network_total;
            } else {
                $this->net_cash_to_safe = $this->cash_total;
                $this->net_digital = $this->network_total - $this->expenses_total;
            }
        }
    }

    // ── Relations ──
    public function safeTransactions(): MorphMany
    {
        return $this->morphMany(SafeTransaction::class, 'reference');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DailyEnvelopeItem::class);
    }
}
