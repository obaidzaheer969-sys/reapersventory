<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Expense::class);

        return view('business.expenses.index', [
            'expenses' => Expense::query()->latest('expense_date')->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Expense::class);

        $data = $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:32'],
            'notes' => ['nullable', 'string'],
        ]);

        Expense::query()->create([
            ...$data,
            'business_id' => $request->user()->business_id,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Expense recorded.');
    }
}
