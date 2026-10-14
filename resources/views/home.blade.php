@extends('layouts.app')

@section('title', 'ReapersVentory — Inventory & Billing SaaS')

@section('content')
<section class="rv-hero relative overflow-hidden text-navy-900">
    <div class="rv-hero__glow" aria-hidden="true"></div>
    <div class="rv-hero__grid" aria-hidden="true"></div>
    <div class="relative mx-auto grid max-w-6xl items-center gap-12 px-4 py-20 lg:grid-cols-2 lg:py-28">
        <div>
            <h1 class="max-w-xl text-3xl font-semibold leading-tight text-navy-900 sm:text-4xl">
                Product management, inventory, and billing for growing businesses.
            </h1>
            <p class="mt-4 max-w-lg text-slate-600">
                One login per business. Track stock, sell with invoices, and manage your subscription — all tenant-isolated.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="rounded-lg bg-brand-500 px-5 py-3 font-semibold text-white shadow-lg shadow-brand-500/25 hover:bg-brand-600">Create your business</a>
                <a href="{{ route('pricing') }}" class="rounded-lg border border-navy-900/15 bg-white/70 px-5 py-3 font-semibold text-navy-900 backdrop-blur hover:bg-white">View pricing</a>
            </div>
        </div>
        <div class="rv-hero__brand flex flex-col items-center text-center lg:items-end lg:text-right">
            <div class="rv-hero__logo-stage">
                <span class="rv-logo block w-64 max-w-full sm:w-80">
                    <img src="{{ asset('images/reapersventory-logo.png') }}" alt="ReapersVentory logo" class="w-full">
                </span>
            </div>
            <p class="mt-5 text-base font-semibold text-navy-900 sm:text-lg">Inventory, Billing, Business Growth.</p>
            <p class="mt-1 text-sm text-slate-500">A Reapers Tech Product</p>
        </div>
    </div>
</section>

<section class="mx-auto max-w-6xl px-4 py-16">
    <h2 class="font-display text-2xl font-bold text-navy-900">Built for multi-business operations</h2>
    <p class="mt-2 max-w-2xl text-slate-600">Each business only sees its own products, inventory, customers, and invoices.</p>
    <div class="mt-10 grid gap-8 md:grid-cols-3">
        <div>
            <h3 class="font-semibold text-brand-600">Inventory control</h3>
            <p class="mt-2 text-sm text-slate-600">Warehouses, stock movements, and low-stock alerts keep shelves accurate.</p>
        </div>
        <div>
            <h3 class="font-semibold text-brand-600">Sales & billing</h3>
            <p class="mt-2 text-sm text-slate-600">Invoices, partial payments, returns, and PDF receipts for every sale.</p>
        </div>
        <div>
            <h3 class="font-semibold text-brand-600">Subscription ready</h3>
            <p class="mt-2 text-sm text-slate-600">Manual bank, Easypaisa, and JazzCash payments with Super Admin approval.</p>
        </div>
    </div>
</section>
@endsection
