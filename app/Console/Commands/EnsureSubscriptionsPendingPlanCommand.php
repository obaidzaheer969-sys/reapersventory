<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnsureSubscriptionsPendingPlanCommand extends Command
{
    protected $signature = 'reapersventory:ensure-pending-plan';

    protected $description = 'Add subscriptions.pending_plan_id if missing (package change until admin approval)';

    public function handle(): int
    {
        if (! Schema::hasTable('subscriptions')) {
            $this->warn('subscriptions table missing.');

            return self::FAILURE;
        }

        if (Schema::hasColumn('subscriptions', 'pending_plan_id')) {
            $this->info('pending_plan_id already exists.');

            return self::SUCCESS;
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('pending_plan_id')
                ->nullable()
                ->after('plan_id')
                ->constrained('plans')
                ->nullOnDelete();
        });

        $this->info('pending_plan_id column added.');

        return self::SUCCESS;
    }
}
