<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreWithdrawalRequest;
use App\Http\Resources\Api\V1\PayoutRequestResource;
use App\Models\User;
use App\Services\PayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function __construct(
        private readonly PayoutService $payouts,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $cooldownHours = max(0, (int) config('earning.payout_cooldown_hours', 24));
        $latestPayout = $user->payoutRequests()
            ->latest('created_at')
            ->latest('id')
            ->first();

        $nextWithdrawalAt = null;

        if ($cooldownHours > 0 && $latestPayout?->created_at !== null) {
            $candidate = $latestPayout->created_at->copy()->addHours($cooldownHours);
            $nextWithdrawalAt = $candidate->isFuture() ? $candidate : null;
        }

        return PayoutRequestResource::collection(
            $user->payoutRequests()->with('account')->latest()->paginate(10),
        )->additional([
            'meta' => [
                'min_amount' => (float) config('earning.min_payout', 50),
                'max_amount' => (float) config('earning.max_payout', 250),
                'cooldown_hours' => $cooldownHours,
                'fee_percent' => (float) config('earning.payout_admin_fee_percent', 10),
                'withdrawals_enabled' => $this->payouts->areEnabled(),
                'next_withdrawal_at' => $nextWithdrawalAt?->toISOString(),
                'quote' => $this->payouts->quote((float) config('earning.min_payout', 50)),
            ],
        ])->response();
    }

    public function store(StoreWithdrawalRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->safe()->only(['payout_account_id', 'amount']);

        $account = $user->payoutAccounts()->find($data['payout_account_id']);

        abort_unless($account !== null, 404, 'Save a wallet account first.');

        $payout = $this->payouts->requestPayout($user, $account, (float) $data['amount']);

        return (new PayoutRequestResource($payout))
            ->response()
            ->setStatusCode(201);
    }
}
