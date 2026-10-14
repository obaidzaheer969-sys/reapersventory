@props(['title', 'subtitle' => null])

<div class="mx-auto max-w-6xl px-4 py-10">
    <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="font-display text-3xl font-bold text-navy-900">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1 text-slate-600">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">{{ $actions ?? '' }}</div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    {{ $slot }}
</div>
