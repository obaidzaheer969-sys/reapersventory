@extends('layouts.guest')

@section('title', config('earning.meta_title'))

@section('content')
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-mint-300">Welcome back</p>
        <h1 class="mt-2 font-display text-2xl font-bold text-white">Log in to your account</h1>
        <p class="mt-1 text-sm text-slate-400">Complete tasks and grow your wallet balance.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-slate-200" for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                   class="mt-1.5 w-full rounded-xl border border-white/10 bg-white/5 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
            @foreach ($errors->get('email') as $message)
                <p class="mt-1 text-xs text-rose-300">{{ $message }}</p>
            @endforeach
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label class="block text-sm font-semibold text-slate-200" for="password">Password</label>
                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-mint-300 hover:text-mint-200">Forgot?</a>
            </div>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="mt-1.5 w-full rounded-xl border border-white/10 bg-white/5 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
            @foreach ($errors->get('password') as $message)
                <p class="mt-1 text-xs text-rose-300">{{ $message }}</p>
            @endforeach
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-300">
            <input type="checkbox" name="remember" class="rounded border-white/20 bg-white/5 text-mint-500 focus:ring-mint-400">
            Remember me
        </label>

        <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-3 text-sm font-bold text-ink-950 shadow-lg shadow-mint-500/25 transition hover:from-mint-300 hover:to-mint-400">
            Login
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-400">
        New account?
        <a href="{{ route('register') }}" class="font-semibold text-mint-300 hover:text-mint-200">Register free</a>
    </p>
@endsection
