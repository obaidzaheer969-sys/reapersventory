<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\ProductReturn;
use App\Models\Sale;
use App\Services\ReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReturnController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', ProductReturn::class);

        return view('business.returns.index', [
            'returns' => ProductReturn::query()
                ->with(['sale', 'warehouse'])
                ->latest('id')
                ->paginate(15),
        ]);
    }

    public function create(Sale $sale): View
    {
        $this->authorize('returnItems', $sale);
        $sale->load(['items.product']);

        return view('business.returns.create', compact('sale'));
    }

    public function store(Request $request, Sale $sale, ReturnService $returns): RedirectResponse
    {
        $this->authorize('returnItems', $sale);

        $data = $request->validate([
            'return_date' => ['required', 'date'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
        ]);

        $data['items'] = collect($data['items'])
            ->filter(fn (array $item) => (float) $item['quantity'] > 0)
            ->values()
            ->all();

        $return = $returns->create($sale, $request->user(), $data);

        return redirect()
            ->route('business.sales.show', $sale)
            ->with('status', "Return {$return->return_number} completed and stock restored.");
    }
}
