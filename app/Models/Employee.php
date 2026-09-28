<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'name',
        'role',
        'monthly_salary',
        'phone',
        'hire_date',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'is_active' => 'boolean',
            'monthly_salary' => 'decimal:2',
        ];
    }

    public static function roleLabels(): array
    {
        return [
            'staff' => 'موظف',
            'trainer' => 'مدرب',
            'reception' => 'استقبال',
            'cleaner' => 'عامل نظافة',
            'worker' => 'عامل يومية',
            'other' => 'أخرى',
        ];
    }

    public function getRoleLabelAttribute(): string
    {
        return static::roleLabels()[$this->role] ?? $this->role;
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function dailyEnvelopeItems(): HasMany
    {
        return $this->hasMany(DailyEnvelopeItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(EmployeeTransaction::class);
    }

    public function getNetSalaryForMonth(int $month, int $year): float
    {
        $baseSalary = $this->monthly_salary ?? 0;

        $bonuses = $this->transactions()
            ->where('type', 'bonus')
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->sum('amount');

        $deductions = $this->transactions()
            ->where('type', 'deduction')
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->sum('amount');

        $directAdvances = $this->expenses()
            ->where('category', 'employee_advance')
            ->whereMonth('expense_date', $month)
            ->whereYear('expense_date', $year)
            ->sum('amount');

        $envelopeAdvances = $this->dailyEnvelopeItems()
            ->whereHas('dailyEnvelope', function ($q) use ($month, $year) {
                $q->whereMonth('envelope_date', $month)
                  ->whereYear('envelope_date', $year);
            })
            ->where('category', 'employee_advance')
            ->sum('amount');

        $paidSalaries = $this->expenses()
            ->whereIn('category', ['salary', 'worker_wages'])
            ->whereMonth('expense_date', $month)
            ->whereYear('expense_date', $year)
            ->sum('amount');

        return max(0, $baseSalary + $bonuses - $deductions - $directAdvances - $envelopeAdvances - $paidSalaries);
    }
}
