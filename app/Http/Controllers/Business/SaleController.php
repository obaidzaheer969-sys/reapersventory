<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Warehouse;
use App\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Sale::class);

        return view('business.sales.index', [
            'sales' => Sale::query()
                ->with(['customer', 'warehouse', 'invoice'])
                ->latest('id')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Sale::class);

        return view('business.sales.create', [
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function show(Sale $sale): View
    {
        $this->authorize('view', $sale);
        $sale->load(['items.product', 'customer', 'warehouse', 'invoice', 'payments', 'returns.items']);

        return view('business.sales.show', compact('sale'));
    }

    public function pay(Request $request, Sale $sale, SaleService $sales): RedirectResponse
    {
        $this->authorize('pay', $sale);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', 'string', 'max:32'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $sales->recordPayment($sale, $request->user(), $data);

        return back()->with('status', 'Payment recorded.');
    }
}
