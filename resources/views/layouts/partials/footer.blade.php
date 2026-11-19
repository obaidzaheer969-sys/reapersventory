<footer class="mt-auto border-t border-navy-900/10 bg-navy-950 text-slate-300">
    <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-8 text-sm sm:flex-row">
        <div class="flex flex-col items-center text-center sm:items-start sm:text-left">
            <x-brand-logo size="md" align="center" :show-text="! request()->routeIs('pricing')" />
            <p class="mt-2 text-[10px] text-slate-500">&copy; {{ date('Y') }}</p>
        </div>
        <p class="text-slate-400">Multi-business inventory, sales, and billing SaaS.</p>
    </div>
</footer>
