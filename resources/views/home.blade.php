<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('earning.brand') }} — {{ config('earning.tagline') }} in PKR</title>
    <meta name="description" content="Complete simple social media tasks and earn PKR. Payouts via Easypaisa, JazzCash, UPaisa and NayaPay.">
    <link rel="icon" href="{{ asset('images/reapersventory-logo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink: { 950: '#060a12', 900: '#0a111e', 850: '#0e1728', 800: '#122035', 700: '#1b2c46' },
                        mint: { 200: '#a7f3d0', 300: '#6ee7b7', 400: '#34d399', 500: '#10b981', 600: '#059669' },
                        gold: { 300: '#fcd34d', 400: '#fbbf24', 500: '#f59e0b' },
                    },
                    fontFamily: {
                        sans: ['Segoe UI', 'Helvetica Neue', 'Arial', 'sans-serif'],
                        display: ['Trebuchet MS', 'Segoe UI', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <style>
        .glow-a { background: radial-gradient(circle at 12% 5%, rgba(16,185,129,.24), transparent 42%); }
        .glow-b { background: radial-gradient(circle at 88% 12%, rgba(59,130,246,.18), transparent 40%); }
        .card { background: linear-gradient(160deg, rgba(255,255,255,.06), rgba(255,255,255,.015)); }
        .grain { background-image: radial-gradient(rgba(255,255,255,.05) 1px, transparent 1px); background-size: 22px 22px; }
    </style>
</head>
<body class="min-h-screen bg-ink-950 font-sans text-slate-100 antialiased">
    <div class="pointer-events-none fixed inset-0 -z-10 glow-a"></div>
    <div class="pointer-events-none fixed inset-0 -z-10 glow-b"></div>
    <div class="pointer-events-none fixed inset-0 -z-10 grain opacity-40"></div>

    <header class="sticky top-0 z-40 border-b border-white/10 bg-ink-950/80 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3.5 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-mint-400 to-mint-600 text-lg font-black text-ink-950 shadow-lg shadow-mint-500/20">₨</span>
                <span class="leading-tight">
                    <span class="block font-display text-lg font-bold tracking-tight text-white">{{ config('earning.brand') }}</span>
                </span>
            </a>

            <nav class="ml-8 hidden items-center gap-6 text-sm font-semibold text-slate-300 md:flex">
                <a href="#platforms" class="hover:text-white">Tasks</a>
                <a href="#how" class="hover:text-white">How it works</a>
                <a href="#wallets" class="hover:text-white">Payouts</a>
                <a href="#faq" class="hover:text-white">FAQ</a>
            </nav>

            <div class="ml-auto flex items-center gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-2.5 text-sm font-bold text-ink-950 shadow-lg shadow-mint-500/20">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-xl px-3.5 py-2.5 text-sm font-semibold text-slate-200 hover:text-white">Login</a>
                    <a href="{{ route('register') }}" class="rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-2.5 text-sm font-bold text-ink-950 shadow-lg shadow-mint-500/20">Register free</a>
                @endauth
            </div>
        </div>
    </header>

    <main>
        <section class="relative overflow-hidden">
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-24">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-mint-400/30 bg-mint-500/10 px-3.5 py-1.5 text-xs font-bold text-mint-200">
                        <span class="h-2 w-2 rounded-full bg-mint-400"></span>
                        Daily tasks · Instant wallet credit
                    </span>

                    <h1 class="mt-6 font-display text-4xl font-bold leading-[1.08] tracking-tight text-white sm:text-5xl lg:text-6xl">
                        Simple tasks complete karein.<br>
                        <span class="bg-gradient-to-r from-mint-300 via-mint-400 to-gold-300 bg-clip-text text-transparent">Real PKR kamayen.</span>
                    </h1>

                    <p class="mt-5 max-w-xl text-base leading-relaxed text-slate-300 sm:text-lg">
                        YouTube subscribe, video watch, Facebook page follow, Instagram, WhatsApp channel aur ads — har task ka reward seedha aap ke wallet me. Payout Easypaisa, JazzCash, UPaisa ya NayaPay par.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="{{ auth()->check() ? route('dashboard') : route('register') }}" class="rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-6 py-3.5 text-sm font-bold text-ink-950 shadow-xl shadow-mint-500/25 transition hover:from-mint-300 hover:to-mint-400">
                            {{ auth()->check() ? 'Go to dashboard' : 'Start earning free' }}
                        </a>
                        <a href="#how" class="rounded-xl border border-white/15 bg-white/5 px-6 py-3.5 text-sm font-bold text-white transition hover:bg-white/10">How it works</a>
                    </div>

                    <div class="mt-10 grid max-w-lg grid-cols-3 gap-3">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-2xl font-bold text-white">{{ number_format($activeTasks) }}</p>
                            <p class="mt-1 text-[11px] uppercase tracking-wider text-slate-400">Active tasks</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-2xl font-bold text-white">{{ number_format($tasksCompleted) }}</p>
                            <p class="mt-1 text-[11px] uppercase tracking-wider text-slate-400">Completed</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-2xl font-bold text-white">{{ number_format($totalPaid, 0) }}</p>
                            <p class="mt-1 text-[11px] uppercase tracking-wider text-slate-400">PKR paid</p>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <div class="absolute -inset-6 rounded-[2.5rem] bg-gradient-to-br from-mint-500/20 via-transparent to-gold-400/10 blur-2xl"></div>
                    <div class="card relative rounded-3xl border border-white/10 p-6 shadow-2xl shadow-black/40">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Wallet balance</p>
                                <p class="mt-1 font-display text-4xl font-bold text-white">PKR 1,250<span class="text-xl text-slate-400">.00</span></p>
                            </div>
                            <span class="rounded-full border border-mint-400/30 bg-mint-500/10 px-3 py-1 text-xs font-bold text-mint-200">Live</span>
                        </div>

                        <div class="mt-6 space-y-3">
                            <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-ink-900/70 p-3.5">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-500/15 text-lg font-black text-rose-400">▶</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-white">YouTube video watch</p>
                                    <p class="text-xs text-slate-400">60 seconds · done</p>
                                </div>
                                <span class="text-sm font-bold text-mint-300">+PKR 40.00</span>
                            </div>
                            <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-ink-900/70 p-3.5">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-500/15 text-lg font-black text-blue-400">f</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-white">Facebook page follow</p>
                                    <p class="text-xs text-slate-400">15 seconds · done</p>
                                </div>
                                <span class="text-sm font-bold text-mint-300">+PKR 15.00</span>
                            </div>
                            <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-ink-900/70 p-3.5">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-pink-500/15 text-lg font-black text-pink-400">◎</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-white">Instagram follow</p>
                                    <p class="text-xs text-slate-400">15 seconds · done</p>
                                </div>
                                <span class="text-sm font-bold text-mint-300">+PKR 15.00</span>
                            </div>
                        </div>

                        <div class="mt-5 rounded-2xl border border-gold-400/20 bg-gold-400/5 p-4">
                            <p class="text-xs font-semibold text-gold-300">Withdraw anytime</p>
                            <p class="mt-1 text-sm text-slate-300">Easypaisa · JazzCash · UPaisa · NayaPay</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="platforms" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-mint-300">Task categories</p>
                <h2 class="mt-2 font-display text-3xl font-bold text-white sm:text-4xl">Har platform ka apna task system</h2>
                <p class="mt-3 text-slate-400">Admin jo seconds set karega, utni der task khula rahega. Timer poora hone ke baad hi reward claim hota hai.</p>
            </div>

            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $platforms = [
                        ['name' => 'YouTube', 'icon' => '▶', 'color' => 'rose', 'items' => ['Subscribe (15 sec)', 'Video watch (custom seconds)']],
                        ['name' => 'Facebook', 'icon' => 'f', 'color' => 'blue', 'items' => ['Page follow (15 sec)', 'Video watch (custom seconds)']],
                        ['name' => 'Instagram', 'icon' => '◎', 'color' => 'pink', 'items' => ['Follow (15 sec)', 'Video watch (custom seconds)']],
                        ['name' => 'WhatsApp', 'icon' => '✆', 'color' => 'emerald', 'items' => ['Channel follow (15 sec)']],
                        ['name' => 'Ads', 'icon' => '★', 'color' => 'amber', 'items' => ['Ad watch (custom seconds)']],
                    ];
                @endphp

                @foreach ($platforms as $platform)
                    <div class="card rounded-2xl border border-white/10 p-5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-{{ $platform['color'] }}-500/15 text-xl font-black text-{{ $platform['color'] }}-400">{{ $platform['icon'] }}</span>
                            <h3 class="text-lg font-bold text-white">{{ $platform['name'] }}</h3>
                        </div>
                        <ul class="mt-4 space-y-2 text-sm text-slate-300">
                            @foreach ($platform['items'] as $item)
                                <li class="flex items-start gap-2"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-mint-400"></span>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach

                <div class="rounded-2xl border border-mint-400/25 bg-mint-500/10 p-5">
                    <p class="text-xs font-bold uppercase tracking-wider text-mint-200">Daily reset</p>
                    <p class="mt-2 text-sm leading-relaxed text-slate-200">Ek task din me sirf ek baar complete hota hai. Raat 12:00 baje ke baad dobara unlock — admin update kare ya na kare.</p>
                </div>
            </div>
        </section>

        <section id="how" class="border-y border-white/10 bg-ink-900/40">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="max-w-2xl">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-mint-300">How it works</p>
                    <h2 class="mt-2 font-display text-3xl font-bold text-white sm:text-4xl">Char simple steps</h2>
                </div>

                <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['01', 'Account banayen', 'Gmail ya email se free register karein.'],
                        ['02', 'Task start karein', 'Link new tab me khulega — login zaroori hai.'],
                        ['03', 'Timer poora karein', 'Admin ke set kiye seconds tak wapas na aayen.'],
                        ['04', 'Wallet me reward', 'Claim karein aur payout request bhejen.'],
                    ] as [$step, $title, $text])
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                            <span class="text-xs font-black tracking-widest text-mint-300">{{ $step }}</span>
                            <h3 class="mt-3 text-base font-bold text-white">{{ $title }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-400">{{ $text }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="wallets" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-2 lg:items-center">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-mint-300">Payouts</p>
                    <h2 class="mt-2 font-display text-3xl font-bold text-white sm:text-4xl">Apne wallet me cash nikalen</h2>
                    <p class="mt-3 text-slate-400">Payout request bhejen — admin verify karke payment bhejta hai. Poora record wallet history me visible rehta hai.</p>

                    <div class="mt-6 grid grid-cols-2 gap-3">
                        @foreach ([['Easypaisa', 'emerald'], ['JazzCash', 'rose'], ['UPaisa', 'violet'], ['NayaPay', 'teal']] as [$wallet, $color])
                            <div class="flex items-center gap-2.5 rounded-xl border border-white/10 bg-white/5 px-4 py-3">
                                <span class="h-2.5 w-2.5 rounded-full bg-{{ $color }}-400"></span>
                                <span class="text-sm font-semibold text-white">{{ $wallet }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card rounded-3xl border border-white/10 p-6">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Wallet summary</p>
                    <div class="mt-4 space-y-3">
                        <div class="flex items-center justify-between rounded-xl border border-white/10 bg-ink-900/60 px-4 py-3">
                            <span class="text-sm text-slate-300">Total earned</span>
                            <span class="text-sm font-bold text-mint-300">PKR 3,480.00</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl border border-white/10 bg-ink-900/60 px-4 py-3">
                            <span class="text-sm text-slate-300">Withdrawn</span>
                            <span class="text-sm font-bold text-white">PKR 2,500.00</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl border border-mint-400/20 bg-mint-500/10 px-4 py-3">
                            <span class="text-sm text-mint-200">Available balance</span>
                            <span class="text-sm font-bold text-mint-200">PKR 980.00</span>
                        </div>
                    </div>
                    <p class="mt-4 text-xs text-slate-500">Minimum payout: PKR {{ number_format((float) config('earning.min_payout'), 0) }}</p>
                </div>
            </div>
        </section>

        @if ($announcements->isNotEmpty())
            <section class="border-y border-white/10 bg-ink-900/40">
                <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                    <h2 class="font-display text-2xl font-bold text-white">Latest announcements</h2>
                    <div class="mt-5 grid gap-4 md:grid-cols-3">
                        @foreach ($announcements as $announcement)
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                                <p class="text-xs text-slate-500">{{ ($announcement->published_at ?? $announcement->created_at)?->format('d M Y') }}</p>
                                <h3 class="mt-2 text-base font-bold text-white">{{ $announcement->title }}</h3>
                                <p class="mt-2 line-clamp-3 text-sm text-slate-400">{{ $announcement->body }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section id="faq" class="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:px-8">
            <h2 class="text-center font-display text-3xl font-bold text-white">Frequently asked questions</h2>
            <div class="mt-8 space-y-3">
                @foreach ([
                    ['Task dobara kab unlock hota hai?', 'Har task din me ek dafa complete hota hai. Raat 12:00 baje (Pakistan time) ke baad automatically unlock ho jata hai — admin action ki zaroorat nahi.'],
                    ['Reward wallet me kab add hota hai?', 'Timer poora hone ke baad jab aap “Claim reward” karte hain, reward foran wallet me add ho jata hai.'],
                    ['Payout kaise milta hai?', 'Wallet page se Easypaisa, JazzCash, UPaisa ya NayaPay account select karein aur payout request bhejen. Admin approve kar ke payment bhejta hai.'],
                    ['Kya main multiple accounts bana sakta hoon?', 'Nahi. Ek user ke liye ek account hai. Duplicate accounts block kiye ja sakte hain.'],
                ] as [$question, $answer])
                    <details class="group rounded-2xl border border-white/10 bg-white/5 p-5">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-semibold text-white">
                            {{ $question }}
                            <span class="text-mint-300 transition group-open:rotate-45">+</span>
                        </summary>
                        <p class="mt-3 text-sm leading-relaxed text-slate-400">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-3xl border border-mint-400/25 bg-gradient-to-br from-mint-500/20 via-ink-900 to-ink-900 px-6 py-12 text-center sm:px-12">
                <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-mint-400/20 blur-3xl"></div>
                <h2 class="font-display text-3xl font-bold text-white sm:text-4xl">Aaj hi earning shuru karein</h2>
                <p class="mx-auto mt-3 max-w-xl text-slate-300">Free registration · Daily tasks · PKR wallet · Fast payouts</p>
                <a href="{{ auth()->check() ? route('dashboard') : route('register') }}" class="mt-7 inline-flex rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-7 py-3.5 text-sm font-bold text-ink-950 shadow-xl shadow-mint-500/25">
                    {{ auth()->check() ? 'Open dashboard' : 'Create free account' }}
                </a>
            </div>
        </section>
    </main>

    <footer class="border-t border-white/10 bg-ink-950">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-6 text-xs text-slate-500 sm:flex-row sm:px-6 lg:px-8">
            <p>&copy; {{ date('Y') }} {{ config('earning.brand') }}. All amounts in PKR.</p>
            <p>Support: {{ config('earning.support_email') }}</p>
        </div>
    </footer>
</body>
</html>
