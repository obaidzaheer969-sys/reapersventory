@extends('layouts.guest')

@section('title', 'Register — '.config('earning.brand'))

@section('content')
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-mint-300">Free account</p>
        <h1 class="mt-2 font-display text-2xl font-bold text-white">Start earning</h1>
        <p class="mt-1 text-sm text-slate-400">Register, complete tasks, and earn PKR.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
        @csrf

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-semibold text-slate-200" for="first_name">First name</label>
                <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" required autofocus autocomplete="given-name"
                       class="mt-1.5 w-full rounded-xl border border-white/10 bg-white/5 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
                @foreach ($errors->get('first_name') as $message)
                    <p class="mt-1 text-xs text-rose-300">{{ $message }}</p>
                @endforeach
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-200" for="last_name">Last name</label>
                <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" required autocomplete="family-name"
                       class="mt-1.5 w-full rounded-xl border border-white/10 bg-white/5 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
                @foreach ($errors->get('last_name') as $message)
                    <p class="mt-1 text-xs text-rose-300">{{ $message }}</p>
                @endforeach
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-200" for="email">Gmail</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                   placeholder="you@example.com"
                   class="mt-1.5 w-full rounded-xl border border-white/10 bg-white/5 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
            @foreach ($errors->get('email') as $message)
                <p class="mt-1 text-xs text-rose-300">{{ $message }}</p>
            @endforeach
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-200" for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   class="mt-1.5 w-full rounded-xl border border-white/10 bg-white/5 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
            @foreach ($errors->get('password') as $message)
                <p class="mt-1 text-xs text-rose-300">{{ $message }}</p>
            @endforeach
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-200" for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                   class="mt-1.5 w-full rounded-xl border border-white/10 bg-white/5 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
        </div>

        <label class="flex items-start gap-2.5 text-sm text-slate-300">
            <input type="checkbox" name="terms" value="1" @checked(old('terms'))
                   class="mt-0.5 rounded border-white/20 bg-white/5 text-mint-500 focus:ring-mint-400">
            <span>I accept the <span class="font-semibold text-slate-200">Terms and Conditions</span>.</span>
        </label>
        @foreach ($errors->get('terms') as $message)
            <p class="text-xs text-rose-300">{{ $message }}</p>
        @endforeach

        <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-3 text-sm font-bold text-ink-950 shadow-lg shadow-mint-500/25 transition hover:from-mint-300 hover:to-mint-400">
            Register
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-400">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-mint-300 hover:text-mint-200">Login</a>
    </p>
@endsection
