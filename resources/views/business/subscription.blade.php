@extends('layouts.app')

@section('title', 'Subscription — ReapersVentory')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-10">
    <h1 class="font-display text-3xl font-bold text-navy-900">Subscription</h1>
    <p class="mt-1 text-slate-600">Manage your ReapersVentory plan, submit manual payments, and track approvals.</p>

    @if (session('status'))
        <div class="mt-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-navy-900">Current status</h2>
            @if ($subscription)
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Plan</dt><dd class="font-medium">{{ $subscription->plan->name }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Status</dt><dd class="font-medium">{{ $subscription->status->label() }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Billing</dt><dd class="font-medium">{{ $subscription->billing_interval->label() }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Period end</dt><dd class="font-medium">{{ optional($subscription->current_period_end)->toDayDateTimeString() ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Access</dt><dd class="font-medium">{{ $activeSubscription ? 'Allowed' : 'Blocked' }}</dd></div>
                    @if ($subscription->cancelled_at)
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Cancelled</dt><dd class="font-medium">{{ $subscription->cancelled_at->toDayDateTimeString() }}</dd></div>
                    @endif
                </dl>

                @if (! $subscription->cancelled_at && $subscription->allowsAccess())
                    <div class="mt-6">
                        <livewire:business.cancel-subscription :subscription-id="$subscription->id" />
                    </div>
                @endif
            @else
                <p class="mt-3 text-sm text-slate-600">No subscription yet. Choose a plan to get started.</p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @if (! $subscription)
                <h2 class="text-lg font-semibold text-navy-900">Choose a plan</h2>
                <div class="mt-4">
                    <livewire:business.choose-plan />
                </div>
            @else
                <h2 class="text-lg font-semibold text-navy-900">Submit payment proof</h2>
                <p class="mt-1 text-sm text-slate-600">Uploading proof does not activate your plan. Super Admin approval is required.</p>
                <div class="mt-4">
                    <livewire:business.submit-payment-proof :subscription-id="$subscription->id" />
                </div>
            @endif
        </div>
    </div>

    <div class="mt-8">
        <livewire:business.update-branding />
    </div>

    <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-navy-900">Payment history</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-slate-500">
                    <tr>
                        <th class="py-2 pr-4">Date</th>
                        <th class="py-2 pr-4">Amount</th>
                        <th class="py-2 pr-4">Method</th>
                        <th class="py-2 pr-4">Period</th>
                        <th class="py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr class="border-b border-slate-100">
                            <td class="py-3 pr-4">{{ $payment->created_at->toDayDateTimeString() }}</td>
                            <td class="py-3 pr-4">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                            <td class="py-3 pr-4">{{ $payment->paymentMethod->name }}</td>
                            <td class="py-3 pr-4">{{ $payment->period_start->format('Y-m-d') }} → {{ $payment->period_end->format('Y-m-d') }}</td>
                            <td class="py-3">{{ $payment->status->label() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-slate-500">No payments submitted yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($methods->isNotEmpty())
        <div class="mt-8 rounded-2xl border border-dashed border-brand-500/40 bg-brand-500/5 p-6">
            <h2 class="font-semibold text-navy-900">Payment instructions</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                @foreach ($methods as $method)
                    <div>
                        <h3 class="font-semibold text-brand-600">{{ $method->name }}</h3>
                        @if ($method->account_name)
                            <p class="mt-1 text-sm text-slate-700">{{ $method->account_name }} — {{ $method->account_number }}</p>
                        @endif
                        <p class="mt-2 text-sm text-slate-600 whitespace-pre-line">{{ $method->instructions }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
