@extends('layouts.guest')

@section('title', 'Register — ReapersVentory')

@section('content')
<h1 class="font-display text-2xl font-bold text-navy-900">Create your business</h1>
<p class="mt-1 text-sm text-slate-600">Public registration creates a Business Owner account only.</p>

<form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
    @csrf

    <div>
        <label class="block text-sm font-medium" for="name">Your name</label>
        <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
               class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
        <x-input-error :messages="$errors->get('name')" />
    </div>

    <div>
        <label class="block text-sm font-medium" for="business_name">Business name</label>
        <input id="business_name" name="business_name" type="text" value="{{ old('business_name') }}" required
               class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
        <x-input-error :messages="$errors->get('business_name')" />
    </div>

    <div>
        <label class="block text-sm font-medium" for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required
               class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
        <x-input-error :messages="$errors->get('email')" />
    </div>

    <div>
        <label class="block text-sm font-medium" for="phone">Phone (optional)</label>
        <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
               class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
        <x-input-error :messages="$errors->get('phone')" />
    </div>

    <div>
        <label class="block text-sm font-medium" for="password">Password</label>
        <input id="password" name="password" type="password" required
               class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
        <x-input-error :messages="$errors->get('password')" />
    </div>

    <div>
        <label class="block text-sm font-medium" for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required
               class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>

    <button type="submit" class="w-full rounded-lg bg-brand-500 px-4 py-3 font-semibold text-white hover:bg-brand-600">
        Register business
    </button>
</form>

<p class="mt-4 text-center text-sm text-slate-600">
    Already registered?
    <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-500">Login</a>
</p>
@endsection
