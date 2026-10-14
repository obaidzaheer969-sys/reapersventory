<?php

namespace App\Livewire\Business;

use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EditProfile extends Component
{
    public string $name = '';

    public string $email = '';

    public ?string $phone = null;

    public ?string $address = null;

    public ?string $city = null;

    public ?string $country = null;

    public string $timezone = 'Asia/Karachi';

    public string $currency = 'PKR';

    public function mount(): void
    {
        $business = Auth::user()->business;

        abort_unless(Auth::user()->can('update', $business), 403);

        $this->fill($business->only([
            'name', 'email', 'phone', 'address', 'city', 'country', 'timezone', 'currency',
        ]));
    }

    public function save(AuditLogger $auditLogger): void
    {
        $user = Auth::user();
        $business = $user->business;

        abort_unless($user->can('update', $business), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('businesses', 'email')->ignore($business->id),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'timezone' => ['required', 'timezone'],
            'currency' => ['required', 'string', 'size:3'],
        ]);

        $old = $business->only(array_keys($validated));
        $business->update($validated);

        $auditLogger->log(
            action: 'business.profile_updated',
            auditable: $business,
            oldValues: $old,
            newValues: $business->only(array_keys($validated)),
        );

        session()->flash('status', 'Business profile updated successfully.');
    }

    public function render(): View
    {
        return view('livewire.business.edit-profile');
    }
}
