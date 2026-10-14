@extends('layouts.guest')

@section('title', 'Forgot password — ReapersVentory')

@section('content')
<h1 class="font-display text-2xl font-bold text-navy-900">Reset password</h1>
<p class="mt-1 text-sm text-slate-600">Enter your email and we will send a reset link.</p>

@if (session('status'))
    <div class="mt-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium" for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
               class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
        <x-input-error :messages="$errors->get('email')" />
    </div>
    <button type="submit" class="w-full rounded-lg bg-brand-500 px-4 py-3 font-semibold text-white hover:bg-brand-600">
        Email reset link
    </button>
</form>
@endsection
