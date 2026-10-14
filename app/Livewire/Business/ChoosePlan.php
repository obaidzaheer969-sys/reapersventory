<?php

namespace App\Livewire\Business;

use App\Enums\BillingInterval;
use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ChoosePlan extends Component
{
    public ?int $planId = null;

    public string $billingInterval = 'monthly';

    public function mount(): void
    {
        $first = Plan::query()->active()->first();
        $this->planId = $first?->id;
    }

    public function start(SubscriptionService $subscriptions): void
    {
        $user = Auth::user();
        $business = $user->business;

        $this->validate([
            'planId' => ['required', 'exists:plans,id'],
            'billingInterval' => ['required', 'in:monthly,yearly'],
        ]);

        $plan = Plan::query()->active()->findOrFail($this->planId);

        $subscriptions->startTrialOrPending(
            $business,
            $plan,
            BillingInterval::from($this->billingInterval),
            $user,
        );

        session()->flash('status', 'Subscription created. Upload a payment proof if payment is required.');

        $this->redirect(route('business.subscription.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.business.choose-plan', [
            'plans' => Plan::query()->active()->with('features')->get(),
        ]);
    }
}
