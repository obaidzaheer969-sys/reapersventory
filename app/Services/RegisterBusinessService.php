<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RegisterBusinessService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly BusinessNotifier $notifier,
    ) {}

    /**
     * Register a business owner and their business in one transaction.
     * Public registration can never create a Super Admin.
     *
     * @param  array{name: string, email: string, password: string, business_name: string, phone?: string|null}  $data
     */
    public function register(array $data): User
    {
        if (($data['role'] ?? null) === UserRole::SuperAdmin->value) {
            throw ValidationException::withMessages([
                'role' => 'Public registration cannot grant Super Admin privileges.',
            ]);
        }

        return DB::transaction(function () use ($data) {
            $business = Business::query()->create([
                'name' => $data['business_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'is_active' => true,
            ]);

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => UserRole::BusinessOwner,
                'business_id' => $business->id,
                'is_active' => true,
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            $this->auditLogger->log(
                action: 'business.registered',
                auditable: $business,
                newValues: [
                    'business_id' => $business->id,
                    'user_id' => $user->id,
                    'role' => UserRole::BusinessOwner->value,
                ],
                user: $user,
            );

            $this->notifier->welcome($user, $business);

            return $user;
        });
    }
}
