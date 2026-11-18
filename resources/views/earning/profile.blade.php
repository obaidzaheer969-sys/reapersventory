@extends('layouts.earning')

@section('title', 'Profile — '.config('earning.brand'))

@section('content')
    @php
        $tz = config('earning.timezone');
        $user = auth()->user();
    @endphp

    <div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-mint-300">Account</p>
        <h1 class="mt-1.5 font-display text-3xl font-bold text-white sm:text-4xl">Profile settings</h1>
        <p class="mt-2 text-sm text-slate-400">Update your name, email, and password.</p>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-5 sm:p-6">
                <h2 class="text-base font-bold text-white">Personal information</h2>

                <form method="POST" action="{{ route('profile.update') }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="mb-1.5 block text-xs font-semibold text-slate-300">Full name</label>
                            <input type="text" name="name" id="name" required maxlength="255" value="{{ old('name', $user->name) }}"
                                   class="w-full rounded-xl border border-white/10 bg-ink-900 px-3.5 py-2.5 text-sm text-white focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
                        </div>
                        <div>
                            <label for="email" class="mb-1.5 block text-xs font-semibold text-slate-300">Email address</label>
                            <input type="email" name="email" id="email" required maxlength="255" value="{{ old('email', $user->email) }}"
                                   class="w-full rounded-xl border border-white/10 bg-ink-900 px-3.5 py-2.5 text-sm text-white focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
                        </div>
                    </div>

                    <div class="border-t border-white/10 pt-4">
                        <h3 class="text-sm font-bold text-white">Change password</h3>
                        <p class="mt-1 text-xs text-slate-400">Fill the fields below only if you want to change your password.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="current_password" class="mb-1.5 block text-xs font-semibold text-slate-300">Current password</label>
                            <input type="password" name="current_password" id="current_password" autocomplete="current-password"
                                   class="w-full rounded-xl border border-white/10 bg-ink-900 px-3.5 py-2.5 text-sm text-white focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
                        </div>
                        <div>
                            <label for="password" class="mb-1.5 block text-xs font-semibold text-slate-300">New password</label>
                            <input type="password" name="password" id="password" autocomplete="new-password"
                                   class="w-full rounded-xl border border-white/10 bg-ink-900 px-3.5 py-2.5 text-sm text-white focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
                        </div>
                        <div>
                            <label for="password_confirmation" class="mb-1.5 block text-xs font-semibold text-slate-300">Confirm password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password"
                                   class="w-full rounded-xl border border-white/10 bg-ink-900 px-3.5 py-2.5 text-sm text-white focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
                        </div>
                    </div>

                    <button type="submit" class="rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-5 py-2.5 text-sm font-bold text-ink-950 shadow-lg shadow-mint-500/20">Save changes</button>
                </form>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-5 text-center">
                @if ($user->avatar)
                    <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="mx-auto h-20 w-20 rounded-2xl object-cover" referrerpolicy="no-referrer">
                @else
                    <span class="mx-auto flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-mint-400 to-mint-600 font-display text-3xl font-black text-ink-950">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                @endif
                <h2 class="mt-3 text-base font-bold text-white">{{ $user->name }}</h2>
                <p class="truncate text-xs text-slate-400">{{ $user->email }}</p>

                <div class="mt-3 flex items-center justify-center gap-2 text-[11px]">
                    @if ($user->google_id)
                        <span class="rounded-full border border-mint-400/30 bg-mint-500/10 px-2.5 py-0.5 font-semibold text-mint-300">Google connected</span>
                    @else
                        <span class="rounded-full border border-white/15 bg-white/5 px-2.5 py-0.5 font-semibold text-slate-400">Email login</span>
                    @endif
                    @if ($user->email_verified_at)
                        <span class="rounded-full border border-white/15 bg-white/5 px-2.5 py-0.5 font-semibold text-slate-400">Verified</span>
                    @endif
                </div>

                <p class="mt-3 text-[11px] text-slate-500">Member since {{ $user->created_at?->timezone($tz)->format('d M Y') }}</p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                <h2 class="text-base font-bold text-white">Earning summary</h2>
                <dl class="mt-3 space-y-2.5 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-400">Wallet balance</dt>
                        <dd class="font-bold text-mint-300">{{ \App\Support\Currency::format($user->wallet_balance) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-400">Total earned</dt>
                        <dd class="font-bold text-white">{{ \App\Support\Currency::format($user->totalEarned()) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-400">Total withdrawn</dt>
                        <dd class="font-bold text-white">{{ \App\Support\Currency::format($user->totalWithdrawn()) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-400">Tasks completed</dt>
                        <dd class="font-bold text-white">{{ $user->completedTaskAttempts()->count() }}</dd>
                    </div>
                </dl>
                <a href="{{ route('wallet.index') }}" class="mt-4 block rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-center text-xs font-bold text-white hover:bg-white/10">Manage wallet →</a>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                <h2 class="text-base font-bold text-white">Need help?</h2>
                <p class="mt-1 text-xs text-slate-400">Contact support for payout or account issues.</p>
                <a href="mailto:{{ config('earning.support_email') }}" class="mt-3 block text-xs font-semibold text-mint-300 hover:text-mint-200">{{ config('earning.support_email') }}</a>
            </div>
        </div>
    </div>
@endsection
