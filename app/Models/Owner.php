<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Owner extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'share_percentage',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'share_percentage' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // ── Scopes ──
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Relations ──
    public function withdrawals(): HasMany
    {
        return $this->hasMany(OwnerWithdrawal::class);
    }

    public function getTotalWithdrawalsAttribute(): float
    {
        return $this->withdrawals()->sum('amount');
    }
}
