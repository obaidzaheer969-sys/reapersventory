<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\LowStockMonitor;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class LowStockController extends Controller
{
    public function __invoke(
        Request $request,
        InventoryService $inventory,
        LowStockMonitor $lowStockMonitor,
        SubscriptionService $subscriptions,
    ): View {
        $this->authorize('viewAny', Product::class);

        $user = $request->user();
        $business = $user->business;
        $activeSubscription = $subscriptions->currentAccessibleSubscription($business);

        $lowStock = [];
        if ($activeSubscription) {
            try {
                $lowStockMonitor->checkBusiness((int) $business->id);
            } catch (Throwable) {
                // ignore
            }

            $lowStock = $inventory->lowStockProducts((int) $business->id);
        }

        return view('business.low-stock.index', [
            'lowStock' => $lowStock,
            'activeSubscription' => $activeSubscription,
        ]);
    }
}
