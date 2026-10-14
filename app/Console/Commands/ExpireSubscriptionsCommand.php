<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Move ended subscriptions into grace/expired states';

    public function handle(SubscriptionService $subscriptions): int
    {
        $count = $subscriptions->markExpiredSubscriptions();
        $this->info("Updated {$count} subscription(s).");

        return self::SUCCESS;
    }
}
