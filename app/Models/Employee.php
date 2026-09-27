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
}
