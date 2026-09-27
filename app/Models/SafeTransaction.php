<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SafeTransaction extends Model
{
    protected $fillable = [
        'transaction_date',
        'type',
        'source',
        'direction',
        'amount',
        'reference_type',
        'reference_id',
        'balance_after_cash',
        'balance_after_digital',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
            'balance_after_cash' => 'decimal:2',
            'balance_after_digital' => 'decimal:2',
        ];
    }

    // ── Labels (Arabic) ──
    public static function typeLabels(): array
    {
        return [
            'daily_deposit' => 'إيداع يومي',
            'bar_deposit' => 'إيراد بار',
            'owner_withdrawal' => 'مسحوبات شريك',
            'expense' => 'مصروفات',
            'debt_payment' => 'تسديد مديونية',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return static::typeLabels()[$this->type] ?? $this->type;
    }

    public function getDirectionLabelAttribute(): string
    {
        return $this->direction === 'in' ? 'وارد' : 'صادر';
    }

    public function getSourceLabelAttribute(): string
    {
        return $this->source === 'cash' ? 'نقدي' : 'شبكة/رقمي';
    }

    // ── Relations ──
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
