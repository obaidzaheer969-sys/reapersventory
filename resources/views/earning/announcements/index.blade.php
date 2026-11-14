@extends('layouts.earning')

@section('title', 'Announcements — '.config('earning.brand'))

@section('content')
    @php
        $tz = config('earning.timezone');
    @endphp

    <div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-mint-300">Updates</p>
        <h1 class="mt-1.5 font-display text-3xl font-bold text-white sm:text-4xl">Announcements</h1>
        <p class="mt-2 text-sm text-slate-400">Naye tasks, payouts aur platform updates ki tamam khabrein yahan milengi.</p>
    </div>

    <div class="mt-6 space-y-4">
        @forelse ($announcements as $announcement)
            <article class="rounded-2xl border p-5 {{ $announcement->is_pinned ? 'border-gold-400/30 bg-gold-400/5' : 'border-white/10 bg-white/5' }}">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($announcement->is_pinned)
                        <span class="rounded-full bg-gold-400/15 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-gold-300">📌 Pinned</span>
                    @endif
                    <span class="text-[11px] font-semibold text-slate-500">{{ ($announcement->published_at ?? $announcement->created_at)?->timezone($tz)->format('d M Y, h:i A') }}</span>
                </div>

                <h2 class="mt-2 font-display text-xl font-bold text-white">{{ $announcement->title }}</h2>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-300">{{ $announcement->body }}</p>

                @if (filled($announcement->author?->name))
                    <p class="mt-3 text-[11px] text-slate-500">Posted by {{ $announcement->author->name }}</p>
                @endif
            </article>
        @empty
            <div class="rounded-2xl border border-white/10 bg-white/5 px-6 py-16 text-center">
                <p class="text-4xl">📣</p>
                <h2 class="mt-3 text-lg font-bold text-white">Abhi koi announcement nahi</h2>
                <p class="mt-1 text-sm text-slate-400">Nayi updates yahan show hongi. Task page zaroor check karte rahein.</p>
            </div>
        @endforelse
    </div>

    @if ($announcements->hasPages())
        <div class="mt-6">{{ $announcements->links() }}</div>
    @endif
@endsection
