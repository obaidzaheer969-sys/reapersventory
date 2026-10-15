<?php

namespace App\Livewire\Business;

use App\Enums\BillingInterval;
use App\Models\PaymentMethod;
use App\Models\Subscription;
use App\Services\SubscriptionPaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class SubmitPaymentProof extends Component
{
    use WithFileUploads;

    public int $subscriptionId;

    public ?int $paymentMethodId = null;

    public string $billingInterval = 'monthly';

    public ?string $reference = null;

    public ?string $notes = null;

    public $proof;

    public function mount(int $subscriptionId): void
    {
        $this->subscriptionId = $subscriptionId;
        $this->paymentMethodId = PaymentMethod::query()->active()->value('id');

        $subscription = $this->ownedSubscription();
        $this->billingInterval = $subscription->billing_interval->value;
    }

    public function submit(SubscriptionPaymentService $payments): void
    {
        $maxKb = (int) config('reapersventory.payment_proof_max_kb', 5120);
        $mimes = implode(',', config('reapersventory.payment_proof_mimes', ['jpg', 'jpeg', 'png', 'pdf']));

        $this->validate([
            'paymentMethodId' => ['required', 'exists:payment_methods,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'proof' => ['required', 'file', "mimes:{$mimes}", "max:{$maxKb}"],
        ]);

        $this->billingInterval = BillingInterval::Monthly->value;
        $subscription = $this->ownedSubscription();

        $payments->submitManualPayment(
            $subscription,
            Auth::user(),
            [
                'payment_method_id' => $this->paymentMethodId,
                'billing_interval' => BillingInterval::Monthly->value,
                'reference' => $this->reference,
                'notes' => $this->notes,
            ],
            $this->proof,
        );

        session()->flash('status', 'Payment proof submitted. Your subscription will activate after Super Admin approval.');

        $this->redirect(route('business.subscription.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.business.submit-payment-proof', [
            'methods' => PaymentMethod::query()->active()->get(),
        ]);
    }

    private function ownedSubscription(): Subscription
    {
        $subscription = Subscription::query()->findOrFail($this->subscriptionId);

        abort_unless(Auth::user()->can('view', $subscription), 403);

        return $subscription;
    }
}
