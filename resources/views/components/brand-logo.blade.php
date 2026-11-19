@props([
    'size' => 'md',
    'align' => 'left',
    'dark' => true,
])

@php
    $logoHeights = [
        'sm' => 'h-9',
        'md' => 'h-10',
        'lg' => 'h-12',
    ];
    $textSizes = [
        'sm' => 'text-xs',
        'md' => 'text-sm',
        'lg' => 'text-base',
    ];
    $logoHeight = $logoHeights[$size] ?? $logoHeights['md'];
    $textSize = $textSizes[$size] ?? $textSizes['md'];
    $alignmentClasses = $align === 'center' ? 'items-center text-center' : 'items-start text-left';
    $textColor = $dark ? 'text-white' : 'text-navy-900';
@endphp

<span {{ $attributes->merge(['class' => 'flex flex-col '.$alignmentClasses.' leading-tight']) }}>
    <img src="{{ asset('images/reapersventory-logo.png') }}" alt="{{ config('earning.brand') }}" class="{{ $logoHeight }} w-auto">
    <span class="mt-1 font-display font-bold tracking-tight {{ $textSize }} {{ $textColor }}">{{ config('earning.brand') }}</span>
</span>
