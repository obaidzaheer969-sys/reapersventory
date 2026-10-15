<?php

namespace App\Services;

use App\Enums\PlanFeatureKey;
use App\Enums\UserRole;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TeamUserService
{
    public function __construct(
        private readonly PlanLimitService $planLimits,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function create(Business $business, User $actor, array $data): User
    {
        $this->planLimits->requireAccessibleSubscription($business);

        $currentCount = User::query()
            ->where('business_id', $business->id)
            ->count();

        $this->planLimits->assertWithinLimit($business, PlanFeatureKey::UsersLimit, $currentCount);

        return DB::transaction(function () use ($business, $actor, $data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => UserRole::BusinessOwner,
                'business_id' => $business->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $this->auditLogger->log(
                action: 'team_user.created',
                auditable: $user,
                newValues: $user->only(['name', 'email', 'role', 'business_id']),
                user: $actor,
            );

            return $user;
        });
    }

    public function deactivate(User $member, User $actor): User
    {
        if ($member->id === $actor->id) {
            throw ValidationException::withMessages([
                'user' => 'You cannot deactivate your own account.',
            ]);
        }

        if ((int) $member->business_id !== (int) $actor->business_id) {
            throw ValidationException::withMessages([
                'user' => 'This user does not belong to your business.',
            ]);
        }

        $activeCount = User::query()
            ->where('business_id', $actor->business_id)
            ->where('is_active', true)
            ->count();

        if ($member->is_active && $activeCount <= 1) {
            throw ValidationException::withMessages([
                'user' => 'At least one active user is required.',
            ]);
        }

        $member->update(['is_active' => false]);

        $this->auditLogger->log(
            action: 'team_user.deactivated',
            auditable: $member,
            newValues: ['is_active' => false],
            user: $actor,
        );

        return $member->refresh();
    }
}
