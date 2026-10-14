<?php

namespace App\Livewire\Business;

use App\Services\BusinessBrandingService;
use App\Services\SubscriptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class UpdateBranding extends Component
{
    use WithFileUploads;

    public string $brandName = '';

    public $logo = null;

    public bool $removeLogo = false;

    public bool $isPaid = false;

    public ?string $currentLogoUrl = null;

    public function mount(SubscriptionService $subscriptions): void
    {
        $user = Auth::user();
        $business = $user->business;

        abort_unless($user->can('update', $business), 403);

        $this->isPaid = $subscriptions->isPaid($business);
        $this->brandName = (string) ($business->brand_name ?? '');
        $this->currentLogoUrl = $business->brandLogoUrl();
    }

    public function save(BusinessBrandingService $branding): void
    {
        $user = Auth::user();
        $business = $user->business;

        abort_unless($user->can('update', $business), 403);

        if (! $this->isPaid) {
            $this->addError('brandName', 'Custom branding unlocks after your subscription is paid and active.');

            return;
        }

        $validated = $this->validate([
            'brandName' => ['nullable', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
            'removeLogo' => ['boolean'],
        ]);

        $branding->update(
            business: $business,
            actor: $user,
            data: ['brand_name' => $validated['brandName'] ?? null],
            logo: $this->logo,
            removeLogo: (bool) $this->removeLogo,
        );

        $business->refresh();
        $this->logo = null;
        $this->removeLogo = false;
        $this->brandName = (string) ($business->brand_name ?? '');
        $this->currentLogoUrl = $business->brandLogoUrl();

        session()->flash('branding_status', 'Branding saved. It appears on your dashboard while your plan is paid.');
    }

    public function render(): View
    {
        return view('livewire.business.update-branding');
    }
}
