@props([
    'size' => 'md',
    'align' => 'left',
])

@php
    $logoHeights = [
        'sm' => 'h-9',
        'md' => 'h-10',
        'lg' => 'h-12',
    ];
    $logoHeight = $logoHeights[$size] ?? $logoHeights['md'];
    $alignmentClasses = $align === 'center' ? 'items-center text-center' : 'items-start text-left';
@endphp

<span {{ $attributes->merge(['class' => 'flex flex-col '.$alignmentClasses.' leading-tight']) }}>
    <img src="{{ asset('images/reapersventory-logo.png') }}" alt="{{ config('earning.brand') }}" class="{{ $logoHeight }} w-auto">
</span>
