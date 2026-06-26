@props(['categories' => []])

<section class="py-16 lg:py-20" style="background-color: var(--color-surface);">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-2 mb-2">
            <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--color-primary-600);"></span>
            <span class="text-xs font-semibold uppercase tracking-widest" style="color: var(--color-text-muted);">Browse by topic</span>
        </div>
        <h2 class="text-2xl sm:text-3xl font-bold mb-10" style="color: var(--color-text-heading);">Categories</h2>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($categories as $cat)
            <a href="{{ route('category.show', $cat->slug) }}" class="group relative overflow-hidden rounded-2xl p-6 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl font-bold transition-transform duration-300 group-hover:scale-110" style="background-color: color-mix(in srgb, {{ $cat->color ?? '#eef2ff' }} 20%, transparent); color: {{ $cat->color ?? 'var(--color-primary-600)' }};">
                        {{ strtoupper(substr($cat->name, 0, 1)) }}
                    </div>
                    <div>
                        <h3 class="text-base font-bold group-hover:underline decoration-2 underline-offset-2" style="color: var(--color-text-heading);">{{ $cat->name }}</h3>
                        <p class="text-sm mt-0.5" style="color: var(--color-text-muted);">{{ $cat->article_count ?? 0 }} articles</p>
                    </div>
                </div>
                <svg class="absolute right-5 top-1/2 -translate-y-1/2 w-5 h-5 transition-all duration-300 group-hover:translate-x-1" style="color: var(--color-text-disabled);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            @empty
            @for($i = 0; $i < 6; $i++)
            <div class="rounded-2xl p-6 animate-pulse" style="background-color: var(--color-surface-elevated);">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl" style="background-color: var(--color-border);"></div>
                    <div class="space-y-2 flex-1">
                        <div class="h-4 w-24 rounded" style="background-color: var(--color-border);"></div>
                        <div class="h-3 w-16 rounded" style="background-color: var(--color-border);"></div>
                    </div>
                </div>
            </div>
            @endfor
            @endforelse
        </div>
    </div>
</section>
