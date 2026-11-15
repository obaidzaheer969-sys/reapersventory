<?php

namespace App\Filament\Widgets;

use App\Enums\PayoutStatus;
use App\Enums\TaskAttemptStatus;
use App\Enums\UserRole;
use App\Models\EarningTask;
use App\Models\PayoutRequest;
use App\Models\TaskAttempt;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EarningStatsOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 0;

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $earners = User::query()->where('role', UserRole::Earner->value);

        $earnersTotal = (clone $earners)->count();
        $earnersToday = (clone $earners)->whereDate('created_at', today())->count();

        $activeTasks = EarningTask::query()->active()->count();

        $completedToday = TaskAttempt::query()
            ->where('status', TaskAttemptStatus::Completed->value)
            ->whereDate('completed_at', today())
            ->count();

        $pendingPayouts = PayoutRequest::query()->where('status', PayoutStatus::Pending->value);
        $pendingCount = (clone $pendingPayouts)->count();
        $pendingAmount = (float) (clone $pendingPayouts)->sum('amount');

        $paidTotal = (float) PayoutRequest::query()
            ->where('status', PayoutStatus::Paid->value)
            ->sum('amount');

        $walletTotal = (float) User::query()->sum('wallet_balance');

        return [
            Stat::make('Earners', number_format($earnersTotal))
                ->description($earnersToday.' joined today')
                ->color('primary'),
            Stat::make('Active tasks', number_format($activeTasks))
                ->description('Live task catalog')
                ->color('success'),
            Stat::make('Completed today', number_format($completedToday))
                ->description('Task attempts finished today')
                ->color('info'),
            Stat::make('Pending payouts', number_format($pendingCount))
                ->description('PKR '.number_format($pendingAmount).' waiting')
                ->color($pendingCount > 0 ? 'warning' : 'gray'),
            Stat::make('Total paid out', 'PKR '.number_format($paidTotal))
                ->description('All time')
                ->color('success'),
            Stat::make('Wallet balances', 'PKR '.number_format($walletTotal))
                ->description('Earner balance held')
                ->color('warning'),
        ];
    }
}
