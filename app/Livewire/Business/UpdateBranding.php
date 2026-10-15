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

    public function updatedLogo(): void
    {
        $this->resetErrorBag('logo');
        $this->removeLogo = false;

        $this->validateOnly('logo', [
            'logo' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,gif,bmp'],
        ]);
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
            'logo' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,gif,bmp'],
            'removeLogo' => ['boolean'],
        ]);

        if (! $this->logo && ! $this->removeLogo && ! filled($validated['brandName']) && ! filled($business->brand_name) && ! filled($business->logo_path)) {
            $this->addError('brandName', 'Enter a display name or choose a logo image.');

            return;
        }

        $branding->update(
            business: $business,
            actor: $user,
            data: ['brand_name' => $validated['brandName'] ?? null],
            logo: $this->logo,
            removeLogo: (bool) $this->removeLogo,
        );

        $business->refresh();
        $this->reset('logo');
        $this->removeLogo = false;
        $this->brandName = (string) ($business->brand_name ?? '');
        $this->currentLogoUrl = $business->brandLogoUrl();

        session()->flash('branding_status', 'Branding saved. Your logo/name now shows on the dashboard.');
    }

    public function render(): View
    {
        return view('livewire.business.update-branding');
    }
}
