@props(['trendingTopics' => []])

<section class="relative overflow-hidden" style="background: linear-gradient(165deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);">
    {{-- Animated grid pattern --}}
    <div class="absolute inset-0 opacity-[0.04]" style="background-image: url('data:image/svg+xml,<svg width=\"80\" height=\"80\" xmlns=\"http://www.w3.org/2000/svg\"><rect width=\"80\" height=\"80\" fill=\"none\" stroke=\"white\" stroke-width=\"0.5\"/></svg>'); background-size: 80px 80px;"></div>

    {{-- Gradient orbs --}}
    <div class="absolute -top-40 -right-40 w-80 h-80 rounded-full opacity-20 blur-3xl" style="background: radial-gradient(circle, var(--color-primary-500), transparent);"></div>
    <div class="absolute -bottom-40 -left-40 w-80 h-80 rounded-full opacity-10 blur-3xl" style="background: radial-gradient(circle, #818cf8, transparent);"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-20 sm:py-28 lg:py-36">
        <div class="max-w-3xl">
            {{-- Badge --}}
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-medium mb-6" style="background-color: rgba(255,255,255,0.08); color: rgba(255,255,255,0.8); border: 1px solid rgba(255,255,255,0.1);">
                <span class="w-2 h-2 rounded-full" style="background-color: var(--color-primary-400);"></span>
                Where ideas find their audience
            </div>

            {{-- Headline --}}
            <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-bold text-white leading-[1.05] tracking-tight">
                Discover stories<br>
                <span class="bg-clip-text text-transparent" style="background: linear-gradient(135deg, var(--color-primary-400), #a78bfa, #c084fc);">that matter</span>
            </h1>

            {{-- Description --}}
            <p class="mt-6 text-lg sm:text-xl max-w-xl leading-relaxed" style="color: #94a3b8;">
                A place to read, write, and deepen your understanding of the topics that matter to you.
            </p>

            {{-- CTAs --}}
            <div class="mt-8 flex flex-col sm:flex-row items-center gap-4">
                <a href="{{ route('search') }}" class="inline-flex items-center gap-2.5 px-6 py-3 rounded-xl text-sm font-semibold text-white transition-all duration-300 hover:opacity-90 hover:scale-[1.02] shadow-lg shadow-indigo-500/25" style="background: linear-gradient(135deg, var(--color-primary-600), var(--color-primary-400));">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Explore articles
                </a>
                @guest
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold transition-all duration-300 hover:bg-white/10" style="border: 1px solid rgba(255,255,255,0.2); color: rgba(255,255,255,0.8);">
                    Start writing
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
                @endguest
            </div>

            {{-- Search bar --}}
            <form action="{{ route('search') }}" method="GET" class="mt-10 max-w-lg">
                <div class="relative group">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 transition-colors duration-200" style="color: #64748b;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="search" name="q" placeholder="Search articles..." class="w-full pl-12 pr-4 py-3.5 rounded-xl text-sm text-white placeholder:text-slate-500 focus:outline-none focus:ring-2 transition-all duration-200" style="background-color: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
                </div>
            </form>

            {{-- Trending topics --}}
            @if($trendingTopics->isNotEmpty())
            <div class="mt-10 flex items-center gap-3 flex-wrap">
                <span class="text-xs font-medium uppercase tracking-wider" style="color: #64748b;">Trending:</span>
                @foreach($trendingTopics as $tag)
                <a href="{{ route('tag.show', $tag->slug) }}" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 hover:bg-white/10 hover:text-white" style="background-color: rgba(255,255,255,0.05); color: #94a3b8; border: 1px solid rgba(255,255,255,0.08);">
                    #{{ $tag->name }}
                </a>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</section>
