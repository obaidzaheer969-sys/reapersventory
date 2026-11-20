<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePayoutAccountRequest;
use App\Http\Resources\Api\V1\PayoutAccountResource;
use App\Models\PayoutAccount;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayoutAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return PayoutAccountResource::collection(
            $user->payoutAccounts()
                ->orderByDesc('is_default')
                ->latest()
                ->get(),
        )->response();
    }

    public function store(StorePayoutAccountRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->safe()->only([
            'provider',
            'account_name',
            'account_number',
            'is_default',
        ]);

        if ($user->payoutAccounts()->count() >= 10) {
            throw ValidationException::withMessages([
                'account_number' => 'You can save up to 10 wallets.',
            ]);
        }

        $accountNumber = trim((string) $data['account_number']);
        $this->assertAccountIsUnique($user, (string) $data['provider'], $accountNumber);

        $account = DB::transaction(function () use ($user, $data, $accountNumber): PayoutAccount {
            $makeDefault = (bool) ($data['is_default'] ?? false)
                || $user->payoutAccounts()->count() === 0;

            if ($makeDefault) {
                $user->payoutAccounts()->update(['is_default' => false]);
            }

            /** @var PayoutAccount $account */
            $account = $user->payoutAccounts()->create([
                'provider' => $data['provider'],
                'account_name' => $data['account_name'],
                'account_number' => $accountNumber,
                'is_default' => $makeDefault,
            ]);

            return $account;
        });

        return (new PayoutAccountResource($account))
            ->response()
            ->setStatusCode(201);
    }

    private function assertAccountIsUnique(User $user, string $provider, string $accountNumber): void
    {
        $exists = $user->payoutAccounts()
            ->where('provider', $provider)
            ->where('account_number', $accountNumber)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'account_number' => 'This wallet account is already saved.',
            ]);
        }
    }
}
