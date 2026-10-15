<?php

namespace App\Http\Controllers\Business;

use App\Enums\PlanFeatureKey;
use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Services\PlanLimitService;
use App\Services\SubscriptionService;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(SubscriptionService $subscriptions, PlanLimitService $planLimits): View
    {
        $this->authorize('viewAny', Warehouse::class);

        $business = auth()->user()->business;
        $subscription = $subscriptions->currentAccessibleSubscription($business);
        $warehousesLimit = $subscription?->plan->featureLimit(PlanFeatureKey::WarehousesLimit);
        $warehouseCount = Warehouse::query()->where('business_id', $business->id)->count();

        return view('business.warehouses.index', [
            'warehouses' => Warehouse::query()->orderByDesc('is_default')->orderBy('name')->paginate(20),
            'warehousesLimit' => $warehousesLimit,
            'warehouseCount' => $warehouseCount,
            'warehousesRemaining' => $planLimits->remaining($business, PlanFeatureKey::WarehousesLimit, $warehouseCount),
        ]);
    }
}
