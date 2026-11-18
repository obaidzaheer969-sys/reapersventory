@extends('layouts.earning')

@section('title', 'Redirecting to Cashmaal — '.config('earning.brand'))

@section('content')
    <div class="mx-auto max-w-lg">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6 text-center sm:p-8">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-mint-400 to-mint-600 text-2xl font-black text-ink-950">
                ₨
            </div>

            <h1 class="mt-4 font-display text-2xl font-bold text-white">Redirecting to Cashmaal</h1>
            <p class="mt-2 text-sm text-slate-400">
                Order <span class="font-mono text-slate-200">{{ $deposit->order_id }}</span> ·
                Total {{ \App\Support\Currency::format($deposit->amount) }}
            </p>
            <p class="mt-1 text-xs text-slate-500">
                Credited to your wallet: {{ \App\Support\Currency::format($deposit->credit_amount) }} · Admin fee {{ \App\Support\Currency::format($deposit->fee) }}
            </p>
            <p class="mt-1 text-xs text-slate-500">Please wait — the payment page is opening automatically. If it does not open, click the button below.</p>

            <form id="cashmaal-form" method="POST" action="{{ $action }}" class="mt-6">
                @foreach ($fields as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach

                <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-3 text-sm font-bold text-ink-950 shadow-lg shadow-mint-500/20">
                    Open Cashmaal payment →
                </button>
            </form>

            <a href="{{ route('deposits.index') }}" class="mt-4 inline-block text-xs font-semibold text-slate-400 hover:text-white">Cancel and return to the deposit page</a>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.setTimeout(function () {
            var form = document.getElementById('cashmaal-form');

            if (form) {
                form.submit();
            }
        }, 400);
    </script>
@endpush
