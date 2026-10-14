@extends('layouts.guest')

@section('title', 'Verify email — ReapersVentory')

@section('content')
<h1 class="font-display text-2xl font-bold text-navy-900">Verify your email</h1>
<p class="mt-2 text-sm text-slate-600">
    Thanks for joining ReapersVentory. If email is delayed on hosting, use the button below to verify instantly.
</p>

@php
    $verifyUrl = session('verification_url', $verificationUrl ?? null);
@endphp

@if (session('status') === 'verification-link-sent')
    <div class="mt-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">
        A verification email was queued/sent. Also check spam — or click the button below.
    </div>
@endif

@if (session('status') === 'verification-link-ready')
    <div class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900">
        Email delivery is unavailable on this server. Verify with the button below.
    </div>
@endif

@if ($errors->any())
    <div class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
        {{ $errors->first() }}
    </div>
@endif

@if ($verifyUrl)
    <a
        href="{{ $verifyUrl }}"
        class="mt-6 flex w-full items-center justify-center rounded-lg bg-navy-900 px-4 py-3 font-semibold text-white hover:bg-navy-800"
    >
        Verify email now
    </a>
    <p class="mt-2 text-center text-xs text-slate-500">This link expires in about 60 minutes.</p>
@endif

<form method="POST" action="{{ route('verification.send') }}" class="mt-4">
    @csrf
    <button type="submit" class="w-full rounded-lg bg-brand-500 px-4 py-3 font-semibold text-white hover:bg-brand-600">
        Resend verification email
    </button>
</form>

<form method="POST" action="{{ route('logout') }}" class="mt-3">
    @csrf
    <button type="submit" class="w-full rounded-lg border border-slate-300 px-4 py-3 font-semibold text-navy-900 hover:bg-slate-50">
        Logout
    </button>
</form>
@endsection
