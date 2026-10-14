@extends('layouts.guest')

@section('title', 'Verify email — ReapersVentory')

@section('content')
<h1 class="font-display text-2xl font-bold text-navy-900">Verify your email</h1>
<p class="mt-2 text-sm text-slate-600">
    Thanks for joining ReapersVentory. Please verify your email address by clicking the link we sent you.
</p>

@if (session('status') === 'verification-link-sent')
    <div class="mt-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">
        A new verification link has been sent to your email address. Check inbox and spam.
    </div>
@endif

@if ($errors->any())
    <div class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
        {{ $errors->first() }}
    </div>
@endif

@if (config('mail.default') === 'log')
    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
        Email is not configured for hosting yet (<code>MAIL_MAILER=log</code>). Update <code>.env</code> with SMTP, then clear config cache.
    </div>
@endif

<form method="POST" action="{{ route('verification.send') }}" class="mt-6">
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
