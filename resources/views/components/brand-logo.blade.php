@props([
    'size' => 'md',
    'align' => 'left',
    'dark' => true,
    'showText' => true,
])

@php
    $logoHeights = [
        'sm' => 'h-9',
        'md' => 'h-10',
        'lg' => 'h-12',
        'xl' => 'h-20',
    ];
    $textSizes = [
        'sm' => 'text-xs',
        'md' => 'text-sm',
        'lg' => 'text-base',
        'xl' => 'text-lg',
    ];
    $logoHeight = $logoHeights[$size] ?? $logoHeights['md'];
    $textSize = $textSizes[$size] ?? $textSizes['md'];
    $alignmentClasses = $align === 'center' ? 'items-center text-center' : 'items-start text-left';
    $textColor = $dark ? 'text-white' : 'text-navy-900';
@endphp

<span {{ $attributes->merge(['class' => 'flex flex-col '.$alignmentClasses.' leading-tight']) }}>
    <img src="{{ asset('images/reapersventory-logo.png') }}" alt="{{ config('earning.brand') }}" class="{{ $logoHeight }} w-auto">
    @if ($showText)
        <span class="mt-1 font-display font-bold tracking-tight {{ $textSize }} {{ $textColor }}">{{ config('earning.brand') }}</span>
    @endif
</span>
