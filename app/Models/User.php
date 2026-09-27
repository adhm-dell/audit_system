<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // ── Filament ──
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    // ── Helpers ──
    public function isOwnerAdmin(): bool
    {
        return $this->role === 'owner_admin';
    }

    public function isAccountant(): bool
    {
        return $this->role === 'accountant';
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    public function canEdit(): bool
    {
        return in_array($this->role, ['owner_admin', 'accountant']);
    }

    // ── Relations ──
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
