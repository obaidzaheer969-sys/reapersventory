<?php

namespace App\Services;

use App\Models\InventoryBalance;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class LowStockMonitor
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly BusinessNotifier $notifier,
    ) {}

    public function checkProduct(Product $product): void
    {
        if (! $product->track_inventory || ! $product->is_active) {
            return;
        }

        $total = InventoryBalance::withoutGlobalScopes()
            ->where('product_id', $product->id)
            ->sum('quantity');

        if (bccomp((string) $total, (string) $product->reorder_level, 3) > 0) {
            return;
        }

        $businessId = (int) $product->business_id;
        $cacheKey = "lowstock:product:{$businessId}:{$product->id}:".now()->toDateString();

        if (! Cache::add($cacheKey, true, now()->endOfDay())) {
            return;
        }

        $products = collect($this->inventory->lowStockProducts($businessId))
            ->filter(fn (Product $item) => $item->id === $product->id)
            ->values();

        if ($products->isEmpty()) {
            $products = collect([$product->fresh(['inventoryBalances']) ?? $product]);
        }

        $business = $product->business()->first();
        if ($business) {
            $this->notifier->lowStock($business, $products);
        }
    }

    public function checkBusiness(int $businessId): void
    {
        $products = collect($this->inventory->lowStockProducts($businessId));

        if ($products->isEmpty()) {
            return;
        }

        $fingerprint = $products->pluck('id')->sort()->values()->implode(',');
        $cacheKey = "business:{$businessId}:low_stock:{$fingerprint}:".now()->toDateString();

        if (! Cache::add($cacheKey, true, now()->endOfDay())) {
            return;
        }

        $business = \App\Models\Business::query()->find($businessId);
        if ($business) {
            $this->notifier->lowStock($business, $products);
        }
    }
}
