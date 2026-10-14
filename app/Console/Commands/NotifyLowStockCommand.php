<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\BusinessNotifier;
use App\Services\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class NotifyLowStockCommand extends Command
{
    protected $signature = 'inventory:notify-low-stock';

    protected $description = 'Email business owners about products at or below reorder level';

    public function handle(InventoryService $inventory, BusinessNotifier $notifier): int
    {
        $businesses = 0;
        $notified = 0;

        Business::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->chunkById(50, function ($chunk) use ($inventory, $notifier, &$businesses, &$notified) {
                foreach ($chunk as $business) {
                    $businesses++;
                    $products = collect($inventory->lowStockProducts((int) $business->id));

                    if ($products->isEmpty()) {
                        continue;
                    }

                    $fingerprint = $products->pluck('id')->sort()->values()->implode(',');
                    $cacheKey = "business:{$business->id}:low_stock:{$fingerprint}:".now()->toDateString();

                    if (! Cache::add($cacheKey, true, now()->endOfDay())) {
                        continue;
                    }

                    $notifier->lowStock($business, $products);
                    $notified++;
                }
            });

        $this->info("Checked {$businesses} business(es); sent {$notified} low-stock alert(s).");

        return self::SUCCESS;
    }
}
