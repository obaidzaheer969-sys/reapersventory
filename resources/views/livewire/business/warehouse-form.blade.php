<div>
    <form wire:submit="save" class="space-y-3">
        <div>
            <label class="block text-sm font-medium">Name</label>
            <input type="text" wire:model="name" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium">Code</label>
            <input type="text" wire:model="code" class="mt-1 w-full rounded-lg px-3 py-2 uppercase ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
            @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium">Address</label>
            <input type="text" wire:model="address" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="isDefault" class="rounded text-brand-500"> Default warehouse</label>
        <button type="submit" class="w-full rounded-lg bg-brand-500 px-4 py-2.5 font-semibold text-white hover:bg-brand-600">Save warehouse</button>
    </form>
</div>
