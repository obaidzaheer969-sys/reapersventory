<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\BusinessNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class NotifyExpiringSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:notify-expiring
                            {--days= : Days before ends_at to warn (default from config)}';

    protected $description = 'Email business owners whose subscriptions end within the warning window';

    public function handle(BusinessNotifier $notifier): int
    {
        $days = (int) ($this->option('days') ?: config('reapersventory.expiring_soon_days', 3));
        $now = now();
        $horizon = $now->copy()->addDays($days);
        $sent = 0;

        Subscription::withoutGlobalScopes()
            ->where('status', SubscriptionStatus::Active->value)
            ->whereNotNull('ends_at')
            ->where('ends_at', '>', $now)
            ->where('ends_at', '<=', $horizon)
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use ($notifier, &$sent) {
                foreach ($subscriptions as $subscription) {
                    $cacheKey = sprintf(
                        'subscription:%d:expiring_soon:%s',
                        $subscription->id,
                        $subscription->ends_at->toDateString(),
                    );

                    if (! Cache::add($cacheKey, true, $subscription->ends_at->copy()->addDay())) {
                        continue;
                    }

                    $notifier->subscriptionEvent($subscription, 'expiring_soon');
                    $sent++;
                }
            });

        $this->info("Sent {$sent} expiring-soon notification(s).");

        return self::SUCCESS;
    }
}
