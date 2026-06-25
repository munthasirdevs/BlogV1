@props(['compact' => false])

<section class="relative overflow-hidden" style="background: linear-gradient(135deg, var(--color-primary-900), var(--color-primary-700), #4f46e5);">
    {{-- Pattern overlay --}}
    <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,<svg width=\"60\" height=\"60\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M30 0v60M0 30h60\" stroke=\"white\" stroke-width=\"0.5\" fill=\"none\"/></svg>'); background-size: 40px 40px;"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16 lg:py-20">
        <div class="mx-auto max-w-2xl text-center">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-medium mb-6" style="background-color: rgba(255,255,255,0.1); color: rgba(255,255,255,0.9);">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Never miss a post
            </div>
            <h2 class="text-3xl sm:text-4xl font-bold text-white leading-tight">Stay in the loop</h2>
            <p class="mt-4 text-base sm:text-lg max-w-md mx-auto" style="color: rgba(255,255,255,0.7);">Get the latest articles, insights, and news delivered straight to your inbox every week.</p>
            <form action="{{ route('newsletter.subscribe') }}" method="POST" class="mt-8 flex flex-col sm:flex-row items-center gap-3 max-w-md mx-auto">
                @csrf
                <div class="relative w-full">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: rgba(255,255,255,0.4);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <input type="email" name="email" placeholder="Enter your email" required class="w-full pl-10 pr-4 py-3 rounded-xl text-sm text-white placeholder:text-white/40 focus:outline-none focus:ring-2 focus:ring-white/30 transition-all" style="background-color: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15);">
                </div>
                <button type="submit" class="shrink-0 w-full sm:w-auto px-6 py-3 rounded-xl text-sm font-semibold transition-all duration-200 hover:opacity-90" style="background-color: white; color: var(--color-primary-700);">Subscribe</button>
            </form>
            <div class="flex items-center justify-center gap-6 mt-6 text-xs" style="color: rgba(255,255,255,0.5);">
                <span class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    No spam
                </span>
                <span class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Unsubscribe anytime
                </span>
            </div>
        </div>
    </div>
</section>
