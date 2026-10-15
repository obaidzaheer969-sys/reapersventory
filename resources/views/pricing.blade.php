@extends('layouts.app')

@section('title', 'Pricing — ReapersVentory')

@section('content')
<section class="bg-gradient-to-b from-navy-950 to-navy-900 px-4 py-16 text-white">
    <div class="mx-auto max-w-6xl text-center">
        <p class="font-display text-3xl font-bold"><span class="text-white">Reapers</span><span class="text-brand-400">Ventory</span></p>
        <h1 class="mt-3 text-3xl font-semibold">Simple monthly and yearly plans</h1>
        <p class="mx-auto mt-3 max-w-2xl text-slate-300">Choose a plan, start an optional trial, then pay via bank transfer, Easypaisa, or JazzCash. Access activates after Super Admin approval.</p>
    </div>
</section>

<section class="mx-auto max-w-6xl px-4 py-14">
    @if ($plans->isEmpty())
        <p class="text-center text-slate-600">Plans will appear here once configured by Super Admin.</p>
    @else
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($plans as $plan)
                <div @class([
                    'rounded-2xl border p-6',
                    'border-brand-500 bg-white shadow-lg shadow-brand-500/10 ring-2 ring-brand-500/30' => $plan->is_featured,
                    'border-slate-200 bg-white' => ! $plan->is_featured,
                ])>
                    <h2 class="text-xl font-bold text-navy-900">{{ $plan->name }}</h2>
                    @if ($plan->description)
                        <p class="mt-2 text-sm text-slate-600">{{ $plan->description }}</p>
                    @endif
                    <p class="mt-4">
                        <span class="text-3xl font-bold text-navy-900">{{ $plan->currency }} {{ number_format($plan->price_monthly, 0) }}</span>
                        <span class="text-sm text-slate-500">/ month</span>
                    </p>
                    <p class="mt-1 text-sm text-slate-500">or {{ $plan->currency }} {{ number_format($plan->price_yearly, 0) }} / year</p>
                    @if ($plan->trial_days > 0)
                        <p class="mt-2 text-sm font-semibold text-brand-600">{{ $plan->trial_days }}-day trial</p>
                    @endif
                    <ul class="mt-6 space-y-2 text-sm text-slate-700">
                        @foreach ($plan->features as $feature)
                            <li class="flex gap-2">
                                <span class="text-brand-500">✓</span>
                                {{ $feature->feature_key->label() }}:
                                {{ $feature->limit_value === null ? 'Unlimited' : number_format($feature->limit_value) }}
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="mt-8 inline-flex w-full items-center justify-center rounded-lg bg-navy-900 px-4 py-3 font-semibold text-white hover:bg-navy-800">
                        Get started
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</section>
@endsection
