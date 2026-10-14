<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        $business = $user->business;

        if (! $business) {
            abort(403, 'No business associated with this account.');
        }

        $subscription = $this->subscriptions->currentAccessibleSubscription($business);

        if (! $subscription) {
            return redirect()
                ->route('business.subscription.index')
                ->withErrors([
                    'subscription' => 'An active subscription is required to continue.',
                ]);
        }

        $request->attributes->set('active_subscription', $subscription);

        return $next($request);
    }
}
