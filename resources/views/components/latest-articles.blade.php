@props(['posts' => []])

<section class="py-16 lg:py-20" style="background-color: var(--color-surface);">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-10">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--color-primary-600);"></span>
                    <span class="text-xs font-semibold uppercase tracking-widest" style="color: var(--color-text-muted);">Latest</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-bold" style="color: var(--color-text-heading);">Recent articles</h2>
            </div>
            @if(method_exists($posts, 'hasPages') && $posts->hasPages())
            <div class="hidden sm:flex items-center gap-2">
                <a href="{{ $posts->previousPageUrl() ?? '#' }}" class="p-2.5 rounded-xl transition-colors duration-200 {{ $posts->onFirstPage() ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-slate-800' }}" style="color: var(--color-text-muted); border: 1px solid var(--color-border);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <a href="{{ $posts->nextPageUrl() ?? '#' }}" class="p-2.5 rounded-xl transition-colors duration-200 {{ !$posts->hasMorePages() ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-slate-800' }}" style="color: var(--color-text-muted); border: 1px solid var(--color-border);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
            @endif
        </div>

        {{-- Grid --}}
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($posts as $post)
            <article class="group flex flex-col rounded-2xl overflow-hidden transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
                {{-- Image --}}
                <a href="{{ route('blog.show', $post->slug) }}" class="aspect-[4/3] overflow-hidden relative">
                    @if($post->featured_image)
                        <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" width="768" height="432" loading="lazy" decoding="async" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" style="aspect-ratio: 16/9;">
                    @else
                        <div class="w-full h-full flex items-center justify-center" style="background: linear-gradient(135deg, var(--color-primary-100), var(--color-primary-50));">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-primary-300);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                        </div>
                    @endif
                    @if($post->category)
                    <span class="absolute top-3 left-3 inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-semibold text-white shadow-lg" style="background: linear-gradient(135deg, var(--color-primary-600), var(--color-primary-400));">
                        {{ $post->category->name }}
                    </span>
                    @endif
                    @if($post->is_featured)
                    <span class="absolute top-3 right-3 inline-flex items-center px-2 py-1 rounded-lg text-[11px] font-semibold text-white shadow-lg" style="background-color: var(--color-warning);">
                        Featured
                    </span>
                    @endif
                </a>

                {{-- Content --}}
                <div class="flex flex-col flex-1 p-5">
                    <h3 class="text-lg font-bold leading-snug" style="color: var(--color-text-heading);">
                        <a href="{{ route('blog.show', $post->slug) }}" class="hover:underline decoration-2 decoration-[var(--color-primary-500)] underline-offset-2">
                            {{ $post->title }}
                        </a>
                    </h3>
                    @if($post->excerpt)
                    <p class="mt-2 text-sm flex-1 line-clamp-2" style="color: var(--color-text-muted);">{{ Str::limit($post->excerpt, 120) }}</p>
                    @endif

                    {{-- Meta --}}
                    <div class="mt-4 pt-4 flex items-center justify-between text-xs" style="border-top: 1px solid var(--color-border); color: var(--color-text-muted);">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold text-white" style="background: linear-gradient(135deg, var(--color-primary-500), var(--color-primary-300));">
                                {{ strtoupper(substr($post->author?->name ?? 'X', 0, 1)) }}
                            </div>
                            <span>{{ $post->author?->name ?? '—' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span>{{ $post->published_at?->format('M d, Y') }}</span>
                            <span>&middot;</span>
                            <span>{{ $post->reading_time }} min</span>
                        </div>
                    </div>
                </div>
            </article>
            @empty
            {{-- Empty State --}}
            <div class="col-span-full flex flex-col items-center justify-center py-20">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4" style="background-color: var(--color-surface-elevated);">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-text-disabled);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                </div>
                <h3 class="text-lg font-semibold" style="color: var(--color-text-heading);">No articles yet</h3>
                <p class="mt-1 text-sm" style="color: var(--color-text-muted);">Check back soon for new stories.</p>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if(method_exists($posts, 'hasPages') && $posts->hasPages())
        <div class="mt-12">
            <div class="flex items-center justify-center gap-2">
                @if($posts->onFirstPage())
                    <span class="flex items-center justify-center w-10 h-10 rounded-xl text-sm font-medium opacity-40 cursor-not-allowed" style="color: var(--color-text-muted); border: 1px solid var(--color-border);">‹</span>
                @else
                    <a href="{{ $posts->previousPageUrl() }}" class="flex items-center justify-center w-10 h-10 rounded-xl text-sm font-medium transition-all duration-200 hover:bg-gray-100 dark:hover:bg-slate-800" style="color: var(--color-text-body); border: 1px solid var(--color-border);">‹</a>
                @endif
                @foreach($posts->getUrlRange(max(1, $posts->currentPage() - 2), min($posts->lastPage(), $posts->currentPage() + 2)) as $page => $url)
                    @if($page == $posts->currentPage())
                        <span class="flex items-center justify-center w-10 h-10 rounded-xl text-sm font-bold text-white shadow-sm" style="background: linear-gradient(135deg, var(--color-primary-600), var(--color-primary-400));">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="flex items-center justify-center w-10 h-10 rounded-xl text-sm font-medium transition-all duration-200 hover:bg-gray-100 dark:hover:bg-slate-800" style="color: var(--color-text-body); border: 1px solid var(--color-border);">{{ $page }}</a>
                    @endif
                @endforeach
                @if($posts->hasMorePages())
                    <a href="{{ $posts->nextPageUrl() }}" class="flex items-center justify-center w-10 h-10 rounded-xl text-sm font-medium transition-all duration-200 hover:bg-gray-100 dark:hover:bg-slate-800" style="color: var(--color-text-body); border: 1px solid var(--color-border);">›</a>
                @else
                    <span class="flex items-center justify-center w-10 h-10 rounded-xl text-sm font-medium opacity-40 cursor-not-allowed" style="color: var(--color-text-muted); border: 1px solid var(--color-border);">›</span>
                @endif
            </div>
        </div>
        @endif
    </div>
</section>
