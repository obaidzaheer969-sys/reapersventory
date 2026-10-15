<?php

namespace App\Livewire\Business;

use App\Livewire\Concerns\RefreshesAfterSave;
use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CustomerForm extends Component
{
    use RefreshesAfterSave;

    public string $name = '';

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $address = null;

    public ?string $notes = null;

    public function save(): void
    {
        $user = Auth::user();
        abort_unless($user->can('create', Customer::class), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        Customer::query()->create([
            ...$validated,
            'business_id' => $user->business_id,
            'is_active' => true,
        ]);

        session()->flash('status', 'Customer created.');
        $this->redirectAndRefresh(route('business.customers.index'));
    }

    public function render(): View
    {
        return view('livewire.business.customer-form');
    }
}
