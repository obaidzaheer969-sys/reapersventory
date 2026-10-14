@extends('layouts.guest')

@section('title', 'Set new password — ReapersVentory')

@section('content')
<h1 class="font-display text-2xl font-bold text-navy-900">Set a new password</h1>

<form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">

    <div>
        <label class="block text-sm font-medium" for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required
               class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
        <x-input-error :messages="$errors->get('email')" />
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
        Reset password
    </button>
</form>
@endsection
