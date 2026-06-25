@props(['trendingPosts' => []])

<section class="py-16 lg:py-20" style="background-color: var(--color-surface-elevated);">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-2 mb-2">
            <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--color-error);"></span>
            <span class="text-xs font-semibold uppercase tracking-widest" style="color: var(--color-text-muted);">Trending now</span>
        </div>
        <h2 class="text-2xl sm:text-3xl font-bold mb-10" style="color: var(--color-text-heading);">Most popular</h2>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @forelse($trendingPosts as $index => $post)
            <a href="{{ route('blog.show', $post->slug) }}" class="group relative flex gap-4 p-4 rounded-2xl transition-all duration-300 hover:shadow-md hover:-translate-y-0.5" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
                <span class="text-3xl sm:text-4xl font-black leading-none shrink-0" style="color: {{ $index === 0 ? 'var(--color-primary-600)' : ($index === 1 ? 'var(--color-primary-400)' : ($index === 2 ? 'var(--color-primary-300)' : 'var(--color-text-disabled)')) }};">0{{ $index + 1 }}</span>
                <div class="min-w-0">
                    @if($post->category)
                    <span class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--color-primary-600);">{{ $post->category->name }}</span>
                    @endif
                    <h3 class="text-sm font-bold leading-snug mt-0.5 group-hover:underline decoration-2 decoration-[var(--color-primary-500)] underline-offset-2" style="color: var(--color-text-heading);">{{ $post->title }}</h3>
                    <div class="flex items-center gap-2 mt-2 text-xs" style="color: var(--color-text-muted);">
                        <span>{{ $post->author?->name ?? '—' }}</span>
                        <span>&middot;</span>
                        <span>{{ $post->views_count ?? 0 }} reads</span>
                    </div>
                </div>
            </a>
            @empty
            <p class="col-span-full text-sm" style="color: var(--color-text-muted);">No trending articles yet.</p>
            @endforelse
        </div>
    </div>
</section>
