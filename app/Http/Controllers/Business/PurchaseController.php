<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Purchase::class);

        return view('business.purchases.index', [
            'purchases' => Purchase::query()
                ->with(['supplier', 'warehouse'])
                ->latest('id')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Purchase::class);

        return view('business.purchases.create', [
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function show(Purchase $purchase): View
    {
        $this->authorize('view', $purchase);

        $purchase->load(['items.product', 'supplier', 'warehouse']);

        return view('business.purchases.show', compact('purchase'));
    }

    public function receive(Request $request, Purchase $purchase, PurchaseService $purchases): RedirectResponse
    {
        $this->authorize('receive', $purchase);

        $purchases->receive($purchase, $request->user());

        return back()->with('status', 'Purchase received and stock updated.');
    }

    public function cancel(Request $request, Purchase $purchase, PurchaseService $purchases): RedirectResponse
    {
        $this->authorize('cancel', $purchase);

        $purchases->cancel($purchase, $request->user());

        return back()->with('status', 'Purchase cancelled.');
    }
}
