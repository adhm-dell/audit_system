<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'type',
        'threshold_amount',
        'days_before_due',
        'is_enabled',
        'notify_roles',
    ];

    protected function casts(): array
    {
        return [
            'threshold_amount' => 'decimal:2',
            'is_enabled' => 'boolean',
            'notify_roles' => 'array',
        ];
    }

    // ── Labels (Arabic) ──
    public static function typeLabels(): array
    {
        return [
            'low_cash_balance' => 'انخفاض رصيد الكاش',
            'low_digital_balance' => 'انخفاض رصيد الشبكة',
            'installment_due_soon' => 'قسط قريب الاستحقاق',
            'installment_overdue' => 'قسط متأخر',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return static::typeLabels()[$this->type] ?? $this->type;
    }
}
