<?php

namespace App\Models;

use App\Enums\PayoutStatus;
use App\Enums\TaskAttemptStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'business_id',
    'is_active',
    'google_id',
    'avatar',
    'wallet_balance',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'wallet_balance' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function taskAttempts(): HasMany
    {
        return $this->hasMany(TaskAttempt::class);
    }

    public function completedTaskAttempts(): HasMany
    {
        return $this->hasMany(TaskAttempt::class)
            ->where('status', TaskAttemptStatus::Completed->value);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function payoutAccounts(): HasMany
    {
        return $this->hasMany(PayoutAccount::class);
    }

    public function payoutRequests(): HasMany
    {
        return $this->hasMany(PayoutRequest::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(EarningTask::class, 'user_id');
    }

    public function liveCampaigns(): HasMany
    {
        return $this->campaigns()->liveCampaigns();
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isBusinessOwner(): bool
    {
        return $this->role === UserRole::BusinessOwner;
    }

    public function isEarner(): bool
    {
        return $this->role === UserRole::Earner;
    }

    public function belongsToBusiness(?int $businessId): bool
    {
        return $this->business_id !== null && $this->business_id === $businessId;
    }

    public function totalEarned(): float
    {
        return (float) $this->completedTaskAttempts()->sum('reward_amount');
    }

    public function totalWatchedSeconds(): int
    {
        return (int) $this->completedTaskAttempts()->sum('watched_seconds');
    }

    public function totalWithdrawn(): float
    {
        return (float) $this->payoutRequests()->where('status', PayoutStatus::Paid->value)->sum('amount');
    }

    public function pendingPayoutTotal(): float
    {
        return (float) $this->payoutRequests()->where('status', PayoutStatus::Pending->value)->sum('amount');
    }

    public function totalCampaignSpend(): float
    {
        return (float) $this->campaigns()->sum('total_cost');
    }

    public function totalDeposited(): float
    {
        return (float) $this->deposits()->completed()->sum('credit_amount');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isSuperAdmin() && $this->is_active;
    }
}
