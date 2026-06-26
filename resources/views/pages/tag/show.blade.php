<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $tag->seo?->meta_title ?? ($tag->name . ' — ' . config('app.name')) }}</title>
    <meta name="description" content="{{ $tag->seo?->meta_description ?? ($tag->description ?? 'Articles tagged with ' . $tag->name) }}">

    @if($tag->seo)
    <link rel="canonical" href="{{ $tag->seo->canonical_url ?? route('tag.show', $tag->slug) }}">
    <meta name="robots" content="{{ $tag->seo->robots_directive ?? 'index,follow' }}">
    <meta property="og:title" content="{{ $tag->seo->og_title ?? $tag->name }}">
    <meta property="og:description" content="{{ $tag->seo->og_description ?? $tag->description }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $tag->seo->twitter_title ?? $tag->seo->og_title ?? $tag->name }}">
    <meta name="twitter:description" content="{{ $tag->seo->twitter_description ?? $tag->seo->og_description ?? $tag->description }}">
    @endif

    <script>(function(){var t=localStorage.getItem('theme');if(t==='dark'||(!t&&window.matchMedia('(prefers-color-scheme:dark)').matches)){document.documentElement.classList.add('dark')}})();</script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "CollectionPage",
        "name": "{{ $tag->name }}",
        "description": "{{ $tag->description ?? 'Articles tagged ' . $tag->name }}",
        "url": "{{ route('tag.show', $tag->slug) }}",
        "about": { "@type": "Thing", "name": "{{ $tag->name }}" }
    }
    </script>
</head>
<body class="font-sans antialiased" style="background-color: var(--color-surface); color: var(--color-text-body); padding-top: 64px;">

    <x-public-header active="blog" />

    <main>
        {{-- Hero --}}
        <div class="py-12" style="background: linear-gradient(135deg, var(--color-primary-900), #1e293b);">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3 mb-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    <span class="text-sm font-medium text-indigo-300">Tag</span>
                </div>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white">#{{ $tag->name }}</h1>
                @if($tag->description)
                <p class="mt-3 text-lg text-slate-300 max-w-2xl">{{ $tag->description }}</p>
                @endif
                <div class="flex items-center gap-4 mt-4 text-sm text-slate-400">
                    <span>{{ $tag->usage_count }} {{ __('articles') }}</span>
                    @if($tag->trending_score > 0)
                    <span class="flex items-center gap-1 text-amber-400">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/></svg>
                        {{ __('Trending') }}
                    </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex gap-8">
                {{-- Main Content --}}
                <div class="flex-1 min-w-0">
                    {{-- Sort --}}
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold" style="color: var(--color-text-heading);">{{ __('Articles') }}</h2>
                        <form method="GET" class="flex items-center gap-2">
                            <select name="sort" onchange="this.form.submit()" class="rounded-lg text-sm border-gray-300 shadow-sm">
                                <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>{{ __('Latest') }}</option>
                                <option value="popular" {{ request('sort') === 'popular' ? 'selected' : '' }}>{{ __('Most Popular') }}</option>
                                <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>{{ __('Oldest') }}</option>
                            </select>
                        </form>
                    </div>

                    {{-- Posts --}}
                    @if($posts->isNotEmpty())
                    <div class="grid gap-6 sm:grid-cols-2">
                        @foreach($posts as $post)
                        <article class="group rounded-2xl overflow-hidden transition-all duration-300 hover:shadow-lg" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
                            @if($post->featured_image)
                            <a href="{{ route('blog.show', $post->slug) }}" class="aspect-[16/9] overflow-hidden block">
                                <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                            </a>
                            @endif
                            <div class="p-5">
                                @if($post->category)
                                <span class="text-xs font-semibold uppercase tracking-wider" style="color: var(--color-primary-600);">{{ $post->category->name }}</span>
                                @endif
                                <h3 class="text-lg font-bold mt-1 leading-snug" style="color: var(--color-text-heading);">
                                    <a href="{{ route('blog.show', $post->slug) }}" class="hover:underline">{{ $post->title }}</a>
                                </h3>
                                <p class="mt-2 text-sm line-clamp-2" style="color: var(--color-text-muted);">{{ Str::limit(strip_tags($post->excerpt ?? $post->content ?? ''), 120) }}</p>
                                <div class="mt-4 pt-4 flex items-center justify-between text-xs" style="border-top: 1px solid var(--color-border); color: var(--color-text-muted);">
                                    <span>{{ $post->published_at?->format('M d, Y') }}</span>
                                    <span>{{ $post->reading_time }} min</span>
                                </div>
                            </div>
                        </article>
                        @endforeach
                    </div>
                    <div class="mt-8">{{ $posts->links() }}</div>
                    @else
                    <div class="text-center py-20">
                        <p class="text-lg" style="color: var(--color-text-muted);">{{ __('No articles with this tag yet.') }}</p>
                    </div>
                    @endif
                </div>

                {{-- Sidebar --}}
                <aside class="hidden lg:block w-64 shrink-0 space-y-6">
                    @if($trendingTags->isNotEmpty())
                    <div class="rounded-xl p-5" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
                        <h4 class="text-xs font-semibold uppercase tracking-wider mb-3" style="color: var(--color-text-muted);">{{ __('Trending Tags') }}</h4>
                        <div class="space-y-2">
                            @foreach($trendingTags as $tt)
                            <a href="{{ route('tag.show', $tt->slug) }}" class="flex items-center justify-between group">
                                <span class="text-sm" style="color: var(--color-text-body);">{{ $tt->name }}</span>
                                <span class="text-xs px-1.5 py-0.5 rounded" style="background-color: var(--color-surface-elevated); color: var(--color-text-muted);">{{ $tt->usage_count }}</span>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Related Tags --}}
                    @if($relatedTags->isNotEmpty())
                    <div class="rounded-xl p-5" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
                        <h4 class="text-xs font-semibold uppercase tracking-wider mb-3" style="color: var(--color-text-muted);">{{ __('Related Tags') }}</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($relatedTags as $rt)
                            <a href="{{ route('tag.show', $rt->slug) }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors hover:bg-gray-100" style="background-color: var(--color-surface-elevated); color: var(--color-text-body); border: 1px solid var(--color-border);">
                                {{ $rt->name }}
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </aside>
            </div>
        </div>

        {{-- Tag Cloud --}}
        @if($allTags->isNotEmpty())
        <section class="py-16" style="background-color: var(--color-surface-elevated);">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-xl font-bold mb-6" style="color: var(--color-text-heading);">{{ __('Explore Topics') }}</h2>
                <div class="flex flex-wrap items-center gap-3">
                    @foreach($allTags as $ct)
                    <a href="{{ route('tag.show', $ct->slug) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium transition-all duration-200 hover:scale-105 hover:shadow-md"
                       style="background-color: var(--color-surface-card); color: var(--color-text-body); border: 1px solid var(--color-border); font-size: {{ max(0.75, min(1.5, 0.75 + ($ct->usage_count / max($allTags->max('usage_count'), 1)) * 0.75)) }}rem;">
                        @if($ct->color)<span class="w-2 h-2 rounded-full" style="background-color: {{ $ct->color }}"></span>@endif
                        {{ $ct->name }}
                        <span class="text-xs opacity-60">({{ $ct->usage_count }})</span>
                    </a>
                    @endforeach
                </div>
            </div>
        </section>
        @endif
    </main>

    <x-public-footer :categories="$categories ?? collect()" />
</body>
</html>
