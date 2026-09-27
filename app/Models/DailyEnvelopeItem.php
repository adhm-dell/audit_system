<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyEnvelopeItem extends Model
{
    protected $fillable = [
        'daily_envelope_id',
        'category',
        'amount',
        'employee_id',
        'paid_to',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public static function categoryLabels(): array
    {
        return [
            'worker_wages' => 'أجور عمال',
            'employee_advance' => 'سلفة موظف',
            'utilities' => 'مرافق',
            'operational' => 'مصروفات تشغيل',
            'other' => 'أخرى',
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        return static::categoryLabels()[$this->category] ?? $this->category;
    }

    public function dailyEnvelope(): BelongsTo
    {
        return $this->belongsTo(DailyEnvelope::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
