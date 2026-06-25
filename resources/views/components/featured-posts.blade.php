@props(['featuredPosts' => []])

<section class="relative overflow-hidden">
    {{-- Section Header --}}
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--color-primary-600);"></span>
                    <span class="text-xs font-semibold uppercase tracking-widest" style="color: var(--color-text-muted);">Featured Stories</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-bold" style="color: var(--color-text-heading);">Editor's picks</h2>
            </div>
            <a href="{{ route('blog.index') }}" class="hidden sm:inline-flex items-center gap-1.5 text-sm font-medium transition-colors" style="color: var(--color-primary-600);">
                View all
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>

        @if($featuredPosts->isNotEmpty())
        <div class="grid gap-6 lg:grid-cols-3">

            {{-- Main Featured Article (spans 2 cols) --}}
            @foreach($featuredPosts->take(2) as $index => $post)
            @if($index === 0)
            <a href="{{ route('blog.show', $post->slug) }}" class="group relative col-span-1 lg:col-span-2 overflow-hidden rounded-2xl transition-all duration-500" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
                <div class="aspect-[16/10] lg:aspect-[21/9] overflow-hidden">
                    @if($post->featured_image)
                        <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                    @else
                        <div class="w-full h-full flex items-center justify-center" style="background: linear-gradient(135deg, var(--color-primary-900), var(--color-primary-700));">
                            <span class="text-6xl font-bold text-white/20">{{ strtoupper(substr($post->title, 0, 1)) }}</span>
                        </div>
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                </div>
                <div class="absolute bottom-0 left-0 right-0 p-6 sm:p-8 lg:p-10">
                    <div class="flex items-center gap-2 mb-3">
                        @if($post->category)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white" style="background-color: var(--color-primary-600);">{{ $post->category->name }}</span>
                        @endif
                        <span class="text-xs text-white/70">{{ $post->reading_time }} min read</span>
                    </div>
                    <h3 class="text-xl sm:text-2xl lg:text-3xl font-bold text-white leading-tight mb-2">{{ $post->title }}</h3>
                    @if($post->excerpt)
                    <p class="text-sm text-white/80 line-clamp-2 max-w-2xl hidden sm:block">{{ Str::limit($post->excerpt, 150) }}</p>
                    @endif
                    <div class="flex items-center gap-3 mt-4 text-xs text-white/60">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold text-white" style="background: linear-gradient(135deg, var(--color-primary-500), var(--color-primary-300));">
                                {{ strtoupper(substr($post->author?->name ?? 'X', 0, 1)) }}
                            </div>
                            <span>{{ $post->author?->name ?? '—' }}</span>
                        </div>
                        <span>&middot;</span>
                        <span>{{ $post->published_at?->format('M d, Y') }}</span>
                    </div>
                </div>
            </a>
            @endif

            {{-- Secondary Article --}}
            @if($index === 1)
            <div class="col-span-1 flex flex-col gap-6">
                <a href="{{ route('blog.show', $post->slug) }}" class="group relative overflow-hidden rounded-2xl transition-all duration-500 flex-1" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
                    <div class="aspect-[16/9] overflow-hidden">
                        @if($post->featured_image)
                            <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                        @else
                            <div class="w-full h-full flex items-center justify-center" style="background: linear-gradient(135deg, var(--color-primary-800), var(--color-primary-600));">
                                <span class="text-4xl font-bold text-white/20">{{ strtoupper(substr($post->title, 0, 1)) }}</span>
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"></div>
                    </div>
                    <div class="absolute bottom-0 left-0 right-0 p-5">
                        <div class="flex items-center gap-2 mb-2">
                            @if($post->category)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium text-white" style="background-color: var(--color-primary-600);">{{ $post->category->name }}</span>
                            @endif
                        </div>
                        <h3 class="text-base sm:text-lg font-bold text-white leading-snug">{{ $post->title }}</h3>
                        <div class="flex items-center gap-2 mt-2 text-xs text-white/60">
                            <span>{{ $post->published_at?->format('M d, Y') }}</span>
                            <span>&middot;</span>
                            <span>{{ $post->reading_time }} min</span>
                        </div>
                    </div>
                </a>

                {{-- Third featured if exists --}}
                @if($featuredPosts->count() > 2)
                @php $third = $featuredPosts[2]; @endphp
                <a href="{{ route('blog.show', $third->slug) }}" class="group relative overflow-hidden rounded-2xl transition-all duration-500 flex-1" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
                    <div class="aspect-[16/9] overflow-hidden">
                        @if($third->featured_image)
                            <img src="{{ $third->featured_image }}" alt="{{ $third->title }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                        @else
                            <div class="w-full h-full flex items-center justify-center" style="background: linear-gradient(135deg, var(--color-primary-800), var(--color-primary-600));">
                                <span class="text-4xl font-bold text-white/20">{{ strtoupper(substr($third->title, 0, 1)) }}</span>
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"></div>
                    </div>
                    <div class="absolute bottom-0 left-0 right-0 p-5">
                        <div class="flex items-center gap-2 mb-2">
                            @if($third->category)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium text-white" style="background-color: var(--color-primary-600);">{{ $third->category->name }}</span>
                            @endif
                        </div>
                        <h3 class="text-base sm:text-lg font-bold text-white leading-snug">{{ $third->title }}</h3>
                        <div class="flex items-center gap-2 mt-2 text-xs text-white/60">
                            <span>{{ $third->published_at?->format('M d, Y') }}</span>
                            <span>&middot;</span>
                            <span>{{ $third->reading_time }} min</span>
                        </div>
                    </div>
                </a>
                @endif
            </div>
            @endif
            @endforeach

        </div>
        @else

        {{-- Skeleton Loading State --}}
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="col-span-1 lg:col-span-2 rounded-2xl overflow-hidden" style="background-color: var(--color-surface-elevated);">
                <div class="aspect-[21/9] animate-pulse" style="background-color: var(--color-border);"></div>
                <div class="p-8 space-y-3">
                    <div class="h-4 w-20 rounded-full animate-pulse" style="background-color: var(--color-border);"></div>
                    <div class="h-8 w-3/4 rounded-lg animate-pulse" style="background-color: var(--color-border);"></div>
                    <div class="h-4 w-1/2 rounded-lg animate-pulse" style="background-color: var(--color-border);"></div>
                </div>
            </div>
            <div class="col-span-1 flex flex-col gap-6">
                <div class="flex-1 rounded-2xl overflow-hidden animate-pulse" style="background-color: var(--color-surface-elevated);">
                    <div class="aspect-[16/9]" style="background-color: var(--color-border);"></div>
                    <div class="p-5 space-y-2">
                        <div class="h-4 w-16 rounded-full" style="background-color: var(--color-border);"></div>
                        <div class="h-6 w-full rounded-lg" style="background-color: var(--color-border);"></div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Mobile View All --}}
        <div class="mt-6 text-center sm:hidden">
            <a href="{{ route('blog.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium" style="color: var(--color-primary-600);">
                View all articles
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
    </div>
</section>
