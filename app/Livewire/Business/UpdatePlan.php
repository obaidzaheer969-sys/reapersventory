<?php

namespace App\Livewire\Business;

use App\Livewire\Concerns\RefreshesAfterSave;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class UpdatePlan extends Component
{
    use RefreshesAfterSave;

    public int $subscriptionId;

    public ?int $planId = null;

    public function mount(int $subscriptionId): void
    {
        $this->subscriptionId = $subscriptionId;
        $subscription = $this->ownedSubscription();
        $this->planId = $subscription->plan_id;
    }

    public function update(SubscriptionService $subscriptions): void
    {
        $this->validate([
            'planId' => ['required', 'exists:plans,id'],
        ]);

        $subscription = $this->ownedSubscription();
        $plan = Plan::query()->active()->findOrFail($this->planId);

        try {
            $subscriptions->changePlan($subscription, $plan, Auth::user());
        } catch (ValidationException $e) {
            $this->addValidationException($e, 'planId');

            return;
        }

        session()->flash(
            'status',
            'Package updated to '.$plan->name.'. Upload payment proof for the new package if payment is required.'
        );

        $this->redirectAndRefresh(route('business.subscription.index'));
    }

    public function render(): View
    {
        $subscription = $this->ownedSubscription()->loadMissing('plan');

        return view('livewire.business.update-plan', [
            'plans' => Plan::query()->active()->with('features')->orderBy('sort_order')->orderBy('price_monthly')->get(),
            'currentPlanId' => $subscription->plan_id,
            'currentPlanName' => $subscription->plan?->name,
        ]);
    }

    private function ownedSubscription(): Subscription
    {
        $subscription = Subscription::query()->findOrFail($this->subscriptionId);

        abort_unless(Auth::user()->can('view', $subscription), 403);

        return $subscription;
    }
}
