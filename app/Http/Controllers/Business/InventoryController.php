<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request, InventoryService $inventory): View
    {
        $this->authorize('viewAny', Product::class);

        $businessId = $request->user()->business_id;

        $balances = InventoryBalance::query()
            ->with(['product', 'warehouse'])
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        $movements = StockMovement::query()
            ->with(['product', 'warehouse'])
            ->latest('id')
            ->limit(15)
            ->get();

        return view('business.inventory.index', [
            'balances' => $balances,
            'movements' => $movements,
            'warehouses' => Warehouse::query()->orderBy('name')->get(),
            'products' => Product::query()->where('track_inventory', true)->orderBy('name')->get(),
            'lowStock' => $inventory->lowStockProducts($businessId),
        ]);
    }
}
