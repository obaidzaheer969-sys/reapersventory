<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-lg font-semibold text-navy-900">Business branding</h2>
    <p class="mt-1 text-sm text-slate-600">
        Show your own logo or display name on the dashboard. Available only while your subscription is <strong>paid &amp; active</strong>.
    </p>

    @if (session('branding_status'))
        <div class="mt-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('branding_status') }}</div>
    @endif

    @unless ($isPaid)
        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
            Unlock branding by completing payment and getting Super Admin approval (status must be Active — not trial).
        </div>
    @endunless

    <form wire:submit="save" class="mt-5 space-y-4">
        <div>
            <label class="block text-sm font-medium text-navy-900">Display name</label>
            <input
                type="text"
                wire:model="brandName"
                @disabled(! $isPaid)
                maxlength="120"
                placeholder="e.g. Acme Traders"
                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm disabled:bg-slate-100 disabled:text-slate-500"
            >
            @error('brandName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-navy-900">Logo</label>
            @if ($currentLogoUrl && ! $removeLogo)
                <div class="mt-2 flex items-center gap-3">
                    <img src="{{ $currentLogoUrl }}" alt="Current logo" class="h-14 w-auto rounded-md border border-slate-200 bg-white object-contain p-1">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="removeLogo" @disabled(! $isPaid) class="rounded border-slate-300">
                        Remove logo
                    </label>
                </div>
            @endif
            <input
                type="file"
                wire:model="logo"
                accept="image/png,image/jpeg,image/webp"
                @disabled(! $isPaid)
                class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-navy-900 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white disabled:opacity-50"
            >
            <p class="mt-1 text-xs text-slate-500">PNG, JPG, or WebP · max 2MB</p>
            @error('logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            <div wire:loading wire:target="logo" class="mt-1 text-xs text-slate-500">Uploading…</div>
        </div>

        <button
            type="submit"
            @disabled(! $isPaid)
            class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600 disabled:cursor-not-allowed disabled:bg-slate-300"
        >
            Save branding
        </button>
    </form>
</div>
