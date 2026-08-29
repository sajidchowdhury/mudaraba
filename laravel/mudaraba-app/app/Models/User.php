<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'employee_id', 'username', 'email', 'password_hash', 'role', 'status',
    'login_start', 'login_end', 'two_factor_secret', 'two_factor_enabled',
    'last_login_at',
])]
#[Hidden(['password_hash', 'remember_token', 'two_factor_secret'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The column used for authentication.
     */
    protected $authPasswordName = 'password_hash';

    protected function casts(): array
    {
        return [
            'two_factor_enabled' => 'boolean',
            'last_login_at' => 'datetime',
            'login_start' => 'datetime:H:i:s',
            'login_end' => 'datetime:H:i:s',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /* -------------------------------------------------------
     * RBAC helpers
     * ----------------------------------------------------- */
    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin'], true);
    }

    public function canBackdate(): bool
    {
        return $this->isSuperadmin();
    }

    /**
     * Resolve the auth password to the hash column.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }
}
