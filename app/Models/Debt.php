<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Debt extends Model
{
    protected $fillable = [
        'direction',
        'type',
        'title',
        'creditor_or_debtor_name',
        'total_amount',
        'remaining_amount',
        'start_date',
        'due_date',
        'status',
        'notes',
        'attachment_path',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'start_date' => 'date',
            'due_date' => 'date',
        ];
    }

    // ── Scopes ──
    public function scopePayable($query)
    {
        return $query->where('direction', 'payable');
    }

    public function scopeReceivable($query)
    {
        return $query->where('direction', 'receivable');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    // ── Helpers ──
    public function isScheduled(): bool
    {
        return in_array($this->type, ['loan', 'installment_debt']);
    }

    public function isIrregular(): bool
    {
        return $this->type === 'irregular_debt';
    }

    public function isPayable(): bool
    {
        return $this->direction === 'payable';
    }

    public function isReceivable(): bool
    {
        return $this->direction === 'receivable';
    }

    // ── Relations ──
    public function installments(): HasMany
    {
        return $this->hasMany(DebtInstallment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }
}
