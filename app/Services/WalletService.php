<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletService
{
    public function credit(
        User $user,
        float $amount,
        WalletTransactionType $type,
        string $description,
        ?Model $reference = null,
    ): WalletTransaction {
        return $this->apply($user, abs($amount), $type, $description, $reference);
    }

    public function debit(
        User $user,
        float $amount,
        WalletTransactionType $type,
        string $description,
        ?Model $reference = null,
    ): WalletTransaction {
        return $this->apply($user, -abs($amount), $type, $description, $reference);
    }

    public function creditFirstActiveAdmin(
        float $amount,
        WalletTransactionType $type,
        string $description,
        ?Model $reference = null,
    ): void {
        $admin = $this->firstActiveAdmin();

        if ($admin === null) {
            return;
        }

        $this->credit($admin, $amount, $type, $description, $reference);
    }

    private function firstActiveAdmin(): ?User
    {
        /** @var User|null $admin */
        $admin = User::query()
            ->where('role', UserRole::SuperAdmin->value)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        return $admin;
    }

    private function apply(
        User $user,
        float $signedAmount,
        WalletTransactionType $type,
        string $description,
        ?Model $reference = null,
    ): WalletTransaction {
        return DB::transaction(function () use ($user, $signedAmount, $type, $description, $reference) {
            /** @var User $locked */
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $current = (float) $locked->wallet_balance;
            $after = round($current + $signedAmount, 2);

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Wallet balance kaafi nahi hai. Available: '.number_format($current, 2).' PKR.',
                ]);
            }

            $locked->forceFill(['wallet_balance' => $after])->save();

            $transaction = WalletTransaction::query()->create([
                'user_id' => $locked->id,
                'type' => $type,
                'amount' => round(abs($signedAmount), 2),
                'balance_after' => $after,
                'description' => $description,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
            ]);

            $user->setAttribute('wallet_balance', $after);

            return $transaction;
        });
    }
}
