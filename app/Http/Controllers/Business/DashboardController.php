<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\LowStockMonitor;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        SubscriptionService $subscriptions,
        InventoryService $inventory,
        LowStockMonitor $lowStockMonitor,
    ): View {
        $user = $request->user();
        $business = $user->business;

        abort_unless($user->can('view', $business), 403);

        $activeSubscription = $subscriptions->currentAccessibleSubscription($business);
        $isPaid = $subscriptions->isPaid($business);

        $lowStock = [];
        if ($activeSubscription) {
            try {
                // Create Alerts entries for any currently low products (once/day).
                $lowStockMonitor->checkBusiness((int) $business->id);
            } catch (Throwable) {
                // ignore
            }

            $lowStock = $inventory->lowStockProducts((int) $business->id);
        }

        $recentNotifications = collect();
        if (Schema::hasTable('notifications')) {
            $recentNotifications = $user->notifications()->latest()->limit(5)->get();
        }

        $recentSales = Sale::query()
            ->with('customer')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('business.dashboard', [
            'user' => $user,
            'business' => $business,
            'activeSubscription' => $activeSubscription,
            'isPaid' => $isPaid,
            'productCount' => Product::query()->count(),
            'warehouseCount' => Warehouse::query()->count(),
            'supplierCount' => Supplier::query()->count(),
            'openPurchases' => Purchase::query()->whereIn('status', ['draft', 'ordered'])->count(),
            'lowStock' => $lowStock,
            'recentNotifications' => $recentNotifications,
            'recentSales' => $recentSales,
            'notificationsReady' => Schema::hasTable('notifications'),
        ]);
    }
}
