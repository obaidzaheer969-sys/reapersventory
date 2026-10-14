<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Category::class);

        return view('business.categories.index', [
            'categories' => Category::query()->withCount('products')->orderBy('name')->paginate(20),
        ]);
    }
}
