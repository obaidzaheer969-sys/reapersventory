@extends('layouts.guest')

@section('title', 'Login — ReapersVentory')

@section('content')
<h1 class="font-display text-2xl font-bold text-navy-900">Welcome back</h1>
<p class="mt-1 text-sm text-slate-600">Sign in to manage your ReapersVentory business.</p>

@if (session('status'))
    <div class="mt-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
    @csrf

    <div>
        <label class="block text-sm font-medium" for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
               class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
        <x-input-error :messages="$errors->get('email')" />
    </div>

    <div>
        <div class="flex items-center justify-between">
            <label class="block text-sm font-medium" for="password">Password</label>
            <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">Forgot?</a>
        </div>
        <input id="password" name="password" type="password" required
               class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
        <x-input-error :messages="$errors->get('password')" />
    </div>

    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-500 focus:ring-brand-500">
        Remember me
    </label>

    <button type="submit" class="w-full rounded-lg bg-brand-500 px-4 py-3 font-semibold text-white hover:bg-brand-600">
        Login
    </button>
</form>

<p class="mt-4 text-center text-sm text-slate-600">
    New here?
    <a href="{{ route('register') }}" class="font-semibold text-brand-600 hover:text-brand-500">Create a business</a>
</p>
@endsection
