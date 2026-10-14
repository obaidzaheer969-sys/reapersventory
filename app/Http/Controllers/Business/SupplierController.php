<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Supplier::class);

        return view('business.suppliers.index', [
            'suppliers' => Supplier::query()->orderBy('name')->paginate(20),
        ]);
    }
}
