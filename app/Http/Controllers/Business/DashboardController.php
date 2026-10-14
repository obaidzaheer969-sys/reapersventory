<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        SubscriptionService $subscriptions,
        InventoryService $inventory,
    ): View {
        $user = $request->user();
        $business = $user->business;

        abort_unless($user->can('view', $business), 403);

        $activeSubscription = $subscriptions->currentAccessibleSubscription($business);
        $isPaid = $subscriptions->isPaid($business);

        return view('business.dashboard', [
            'user' => $user,
            'business' => $business,
            'activeSubscription' => $activeSubscription,
            'isPaid' => $isPaid,
            'productCount' => Product::query()->count(),
            'warehouseCount' => Warehouse::query()->count(),
            'supplierCount' => Supplier::query()->count(),
            'openPurchases' => Purchase::query()->whereIn('status', ['draft', 'ordered'])->count(),
            'lowStock' => $activeSubscription
                ? $inventory->lowStockProducts($business->id)
                : [],
        ]);
    }
}
