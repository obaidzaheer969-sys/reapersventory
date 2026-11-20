<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TaskAttemptStatus;
use App\Enums\WalletTransactionType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TransactionResource;
use App\Models\TaskAttempt;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EarningsController extends Controller
{
    public function __construct(
        private readonly TaskService $tasks,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        [$start, $end] = $this->tasks->todayRangeUtc();

        $todayEarned = (float) TaskAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', TaskAttemptStatus::Completed->value)
            ->whereBetween('completed_at', [$start, $end])
            ->sum('reward_amount');

        $transactions = $user->walletTransactions()
            ->whereIn('type', [
                WalletTransactionType::TaskReward->value,
                WalletTransactionType::Bonus->value,
            ])
            ->latest()
            ->limit(20)
            ->get();

        return TransactionResource::collection($transactions)
            ->additional([
                'meta' => [
                    'wallet_balance' => (float) $user->wallet_balance,
                    'total_earned' => $user->totalEarned(),
                    'today_earned' => $todayEarned,
                    'tasks_completed_today' => $this->tasks->completedTodayCount($user),
                    'next_reset_at' => $this->tasks->nextResetAt()->toISOString(),
                ],
            ])
            ->response();
    }
}
