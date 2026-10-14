<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Customer::class);

        return view('business.customers.index', [
            'customers' => Customer::query()->orderBy('name')->paginate(20),
        ]);
    }
}
