<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'expense_date',
        'title',
        'category',
        'cash_amount',
        'instapay_amount',
        'wallet_amount',
        'fawry_amount',
        'employee_id',
        'paid_to',
        'attachment_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
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

    // ── Labels (Arabic) ──
    public static function categoryLabels(): array
    {
        return [
            'construction' => 'إنشاءات',
            'equipment' => 'معدات',
            'maintenance' => 'صيانة',
            'renovation' => 'تجديد',
            'utilities' => 'مرافق',
            'worker_wages' => 'أجور عمال',
            'employee_advance' => 'سلفة موظف',
            'salary' => 'راتب',
            'operational' => 'مصروفات تشغيل',
            'other' => 'أخرى',
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        return static::categoryLabels()[$this->category] ?? $this->category;
    }

    // ── Relations ──
    public function safeTransactions(): MorphMany
    {
        return $this->morphMany(SafeTransaction::class, 'reference');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
