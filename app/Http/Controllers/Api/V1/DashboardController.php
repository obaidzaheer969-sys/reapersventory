<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PayoutStatus;
use App\Enums\TaskAttemptStatus;
use App\Enums\WalletTransactionType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DashboardResource;
use App\Models\Announcement;
use App\Models\EarningTask;
use App\Models\PayoutRequest;
use App\Models\TaskAttempt;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __invoke(Request $request, TaskService $tasks): DashboardResource
    {
        /** @var User $user */
        $user = $request->user();

        [$start, $end] = $tasks->todayRangeUtc();

        $recentTransactions = $user->walletTransactions()
            ->latest()
            ->limit(6)
            ->get();

        $this->annotateTransactionFees($recentTransactions);

        return new DashboardResource([
            'wallet_balance' => (float) $user->wallet_balance,
            'today_earned' => (float) TaskAttempt::query()
                ->where('user_id', $user->id)
                ->where('status', TaskAttemptStatus::Completed->value)
                ->whereBetween('completed_at', [$start, $end])
                ->sum('reward_amount'),
            'total_earned' => $user->totalEarned(),
            'available_tasks' => $tasks->remainingTodayCount($user),
            'active_campaigns' => EarningTask::query()
                ->liveCampaigns()
                ->where('user_id', '!=', $user->id)
                ->count(),
            'pending_withdrawals' => $user->payoutRequests()
                ->where('status', PayoutStatus::Pending->value)
                ->count(),
            'announcements' => Announcement::query()
                ->visible()
                ->latestFirst()
                ->limit(5)
                ->get(),
            'recent_transactions' => $recentTransactions,
        ]);
    }

    /**
     * @param  Collection<int, WalletTransaction>  $transactions
     */
    private function annotateTransactionFees(Collection $transactions): void
    {
        $payoutIds = $transactions
            ->where('type', WalletTransactionType::PayoutRequest)
            ->pluck('reference_id')
            ->filter()
            ->all();

        $fees = $payoutIds !== []
            ? PayoutRequest::query()->whereIn('id', $payoutIds)->pluck('fee', 'id')
            : collect();

        foreach ($transactions as $transaction) {
            $transaction->setAttribute(
                'api_fee',
                $fees->get($transaction->reference_id, 0),
            );
        }
    }
}
