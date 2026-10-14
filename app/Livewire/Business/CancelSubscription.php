<?php

namespace App\Livewire\Business;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CancelSubscription extends Component
{
    public int $subscriptionId;

    public function cancel(SubscriptionService $subscriptions): void
    {
        $subscription = Subscription::query()->findOrFail($this->subscriptionId);
        $user = Auth::user();

        abort_unless($user->can('cancel', $subscription), 403);

        $subscriptions->cancel($subscription, $user);

        session()->flash('status', 'Subscription cancelled. Access continues until the current period ends if still active.');

        $this->redirect(route('business.subscription.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.business.cancel-subscription');
    }
}
