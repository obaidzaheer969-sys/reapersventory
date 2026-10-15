<?php

namespace App\Console\Commands;

use App\Support\EnsuresPendingPlanColumn;
use Illuminate\Console\Command;

class EnsureSubscriptionsPendingPlanCommand extends Command
{
    protected $signature = 'reapersventory:ensure-pending-plan';

    protected $description = 'Add subscriptions.pending_plan_id if missing (package change until admin approval)';

    public function handle(): int
    {
        if (EnsuresPendingPlanColumn::run()) {
            $this->info('pending_plan_id is ready.');

            return self::SUCCESS;
        }

        $this->error('Could not ensure pending_plan_id column.');

        return self::FAILURE;
    }
}
