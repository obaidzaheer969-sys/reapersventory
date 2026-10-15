<?php

namespace App\Http\Controllers\Business;

use App\Enums\PlanFeatureKey;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PlanLimitService;
use App\Services\SubscriptionService;
use Illuminate\View\View;

class TeamUserController extends Controller
{
    public function index(SubscriptionService $subscriptions, PlanLimitService $planLimits): View
    {
        $this->authorize('viewAny', User::class);

        $user = auth()->user();
        $business = $user->business;
        $subscription = $subscriptions->currentAccessibleSubscription($business);
        $usersLimit = $subscription?->plan->featureLimit(PlanFeatureKey::UsersLimit);
        $userCount = User::query()->where('business_id', $business->id)->count();

        return view('business.team-users.index', [
            'users' => User::query()
                ->where('business_id', $business->id)
                ->orderBy('name')
                ->paginate(20),
            'usersLimit' => $usersLimit,
            'userCount' => $userCount,
            'usersRemaining' => $planLimits->remaining($business, PlanFeatureKey::UsersLimit, $userCount),
        ]);
    }
}
