<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\LowStockMonitor;
use Illuminate\Console\Command;

class NotifyLowStockCommand extends Command
{
    protected $signature = 'inventory:notify-low-stock';

    protected $description = 'Notify business owners about products at or below reorder level';

    public function handle(LowStockMonitor $monitor): int
    {
        $businesses = 0;

        Business::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->chunkById(50, function ($chunk) use ($monitor, &$businesses) {
                foreach ($chunk as $business) {
                    $businesses++;
                    $monitor->checkBusiness((int) $business->id);
                }
            });

        $this->info("Checked {$businesses} business(es) for low-stock alerts.");

        return self::SUCCESS;
    }
}
