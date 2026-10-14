<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request, SubscriptionService $subscriptions): View
    {
        $business = $request->user()->business;

        $subscription = Subscription::query()
            ->with('plan.features')
            ->where('business_id', $business->id)
            ->latest('id')
            ->first();

        $payments = SubscriptionPayment::query()
            ->with(['paymentMethod', 'proofs'])
            ->where('business_id', $business->id)
            ->latest('id')
            ->limit(20)
            ->get();

        $plans = Plan::query()->active()->with('features')->get();
        $methods = PaymentMethod::query()->active()->get();
        $active = $subscriptions->currentAccessibleSubscription($business);

        return view('business.subscription', [
            'business' => $business,
            'subscription' => $subscription,
            'activeSubscription' => $active,
            'payments' => $payments,
            'plans' => $plans,
            'methods' => $methods,
        ]);
    }
}
