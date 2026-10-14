<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function __invoke(): View
    {
        $plans = Plan::query()->active()->with('features')->get();

        return view('pricing', compact('plans'));
    }
}
