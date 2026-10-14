<?php

namespace App\Livewire\Business;

use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WarehouseForm extends Component
{
    public string $name = '';

    public string $code = '';

    public ?string $address = null;

    public bool $isDefault = false;

    public function save(WarehouseService $warehouses): void
    {
        $user = Auth::user();
        abort_unless($user->can('create', Warehouse::class), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'isDefault' => ['boolean'],
        ]);

        $warehouses->create($user->business, $user, [
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'address' => $validated['address'],
            'is_default' => $validated['isDefault'],
            'is_active' => true,
        ]);

        $this->reset(['name', 'code', 'address', 'isDefault']);
        session()->flash('status', 'Warehouse created.');
    }

    public function render(): View
    {
        return view('livewire.business.warehouse-form');
    }
}
