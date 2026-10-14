<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Warehouse::class);

        return view('business.warehouses.index', [
            'warehouses' => Warehouse::query()->orderByDesc('is_default')->orderBy('name')->paginate(20),
        ]);
    }
}
