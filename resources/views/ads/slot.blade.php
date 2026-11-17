@php
    $code = $adSlots[$name] ?? '';
    $shell = ($variant ?? 'dark') === 'dark'
        ? 'border-white/10 bg-white/5 text-slate-500'
        : 'border-slate-200 bg-white text-slate-500';
@endphp

@if ($code !== '')
    <div class="my-4 overflow-hidden rounded-2xl border px-2 py-2 text-center {{ $shell }}" data-ad-slot="{{ $name }}" aria-label="Advertisement">
        <p class="mb-1 text-[9px] font-bold uppercase tracking-[0.2em]">Advertisement</p>
        {!! $code !!}
    </div>
@endif
