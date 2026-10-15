<div>
    <form wire:submit="save" class="space-y-3">
        <div>
            <label class="block text-sm font-medium" for="teamUserName">Name</label>
            <input id="teamUserName" type="text" wire:model="name" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium" for="teamUserEmail">Email</label>
            <input id="teamUserEmail" type="email" wire:model="email" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium" for="teamUserPassword">Password</label>
            <input id="teamUserPassword" type="password" wire:model="password" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="w-full rounded-lg bg-brand-500 px-4 py-2.5 font-semibold text-white hover:bg-brand-600" wire:loading.attr="disabled">
            Add team user
        </button>
    </form>
</div>
