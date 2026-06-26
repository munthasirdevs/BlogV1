<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $post->title }} — {{ config('app.name', 'XenonBlog') }}</title>
    <meta name="description" content="{{ $post->excerpt ?? Str::limit(strip_tags($post->content), 160) }}">

    <script>
        (function() {
            var theme = localStorage.getItem('theme');
            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .reading-progress { position: fixed; top: 0; left: 0; right: 0; height: 3px; z-index: 9999; background: transparent; }
        .reading-progress-bar { height: 100%; width: 0%; transition: width 0.1s linear; background: linear-gradient(90deg, var(--color-primary-600), var(--color-primary-400)); }
        .article-body h2 { font-size: 1.5rem; font-weight: 700; margin-top: 2rem; margin-bottom: 0.75rem; color: var(--color-text-heading); }
        .article-body h3 { font-size: 1.25rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: 0.5rem; color: var(--color-text-heading); }
        .article-body p { font-size: 1.125rem; line-height: 1.8; margin-bottom: 1.25rem; color: var(--color-text-body); }
        .article-body ul, .article-body ol { font-size: 1.125rem; line-height: 1.8; margin-bottom: 1.25rem; padding-left: 1.5rem; color: var(--color-text-body); }
        .article-body li { margin-bottom: 0.25rem; }
        .article-body blockquote { border-left: 4px solid var(--color-primary-500); padding-left: 1rem; margin: 1.5rem 0; font-style: italic; color: var(--color-text-muted); }
        .article-body img { border-radius: 0.75rem; margin: 1.5rem 0; max-width: 100%; height: auto; }
        .article-body pre { border-radius: 0.75rem; padding: 1.25rem; overflow-x: auto; margin: 1.5rem 0; background-color: #0f172a !important; color: #e2e8f0; }
        .article-body code { font-size: 0.875rem; }
        .article-body a { color: var(--color-primary-600); text-decoration: underline; text-underline-offset: 2px; }
        .article-body a:hover { color: var(--color-primary-700); }
    </style>
</head>
<body class="font-sans antialiased" style="background-color: var(--color-surface); color: var(--color-text-body); padding-top: 64px;">

    {{-- Reading Progress Bar --}}
    <div class="reading-progress" id="progressBar">
        <div class="reading-progress-bar" id="progressFill"></div>
    </div>

    <x-public-header active="blog" />

    <main>

        {{-- Article Header --}}
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 pt-12 pb-8">
            {{-- Breadcrumb --}}
            <div class="flex items-center gap-2 text-sm mb-6" style="color: var(--color-text-muted);">
                <a href="{{ route('blog.index') }}" class="hover:underline">Blog</a>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                @if($post->category)
                <a href="{{ route('category.show', $post->category->slug) }}" class="hover:underline">{{ $post->category->name }}</a>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                @endif
                <span style="color: var(--color-text-heading);">{{ $post->title }}</span>
            </div>

            {{-- Category --}}
            @if($post->category)
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold text-white mb-4" style="background: linear-gradient(135deg, var(--color-primary-600), var(--color-primary-400));">
                {{ $post->category->name }}
            </span>
            @endif

            {{-- Title --}}
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold leading-tight tracking-tight" style="color: var(--color-text-heading);">{{ $post->title }}</h1>

            {{-- Meta --}}
            <div class="flex flex-wrap items-center gap-4 mt-6 pb-8" style="border-bottom: 1px solid var(--color-border);">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white" style="background: linear-gradient(135deg, var(--color-primary-600), var(--color-primary-400));">
                        {{ strtoupper(substr($post->author?->name ?? 'X', 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium" style="color: var(--color-text-heading);">{{ $post->author?->name ?? '—' }}</p>
                        <div class="flex items-center gap-2 text-xs" style="color: var(--color-text-muted);">
                            <span>{{ $post->published_at?->format('F d, Y') }}</span>
                            <span>&middot;</span>
                            <span>{{ $post->reading_time }} min read</span>
                            @if($post->views_count)
                            <span>&middot;</span>
                            <span>{{ number_format($post->views_count) }} views</span>
                            @endif
                        </div>
                    </div>
                </div>
                {{-- Social share --}}
                <div class="flex items-center gap-2 ml-auto">
                    <span class="text-xs font-medium mr-1" style="color: var(--color-text-muted);">Share</span>
                    <a href="#" class="p-2 rounded-lg transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-slate-800" style="color: var(--color-text-muted);" aria-label="Share on Twitter">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="#" class="p-2 rounded-lg transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-slate-800" style="color: var(--color-text-muted);" aria-label="Share on Facebook">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="#" class="p-2 rounded-lg transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-slate-800" style="color: var(--color-text-muted);" aria-label="Copy link" onclick="navigator.clipboard.writeText(window.location.href);alert('Link copied!');">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    </a>
                </div>
            </div>
        </div>

        {{-- Hero Image --}}
        @if($post->featured_image)
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 pb-12">
            <div class="aspect-[21/9] rounded-2xl overflow-hidden shadow-xl">
                <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="w-full h-full object-cover">
            </div>
        </div>
        @endif

        {{-- Article Content --}}
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 pb-16">
            <div class="flex gap-12">

                {{-- Sticky Sidebar (Desktop) --}}
                <aside class="hidden lg:block shrink-0 w-56">
                    <div class="sticky top-24 space-y-6">
                        @if($post->tags->isNotEmpty())
                        <div>
                            <h4 class="text-xs font-semibold uppercase tracking-wider mb-3" style="color: var(--color-text-muted);">Tags</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($post->tags as $tag)
                                <a href="{{ route('tag.show', $tag->slug) }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors duration-200" style="background-color: var(--color-surface-elevated); color: var(--color-text-muted); border: 1px solid var(--color-border);">{{ $tag->name }}</a>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <x-category-widget :categories="$categories" />
                    </div>
                </aside>

                {{-- Content --}}
                <article class="flex-1 min-w-0">
                    @if($post->excerpt)
                    <p class="text-lg sm:text-xl leading-relaxed mb-8 font-medium" style="color: var(--color-text-heading);">{{ $post->excerpt }}</p>
                    @endif

                    <div class="article-body">
                        {!! $post->content !!}
                    </div>

                    {{-- Author Card --}}
                    <div class="mt-12 p-6 rounded-2xl flex flex-col sm:flex-row items-start gap-5" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center text-xl font-bold text-white shrink-0" style="background: linear-gradient(135deg, var(--color-primary-600), var(--color-primary-400));">
                            {{ strtoupper(substr($post->author?->name ?? 'X', 0, 1)) }}
                        </div>
                        <div>
                            <h4 class="text-base font-bold" style="color: var(--color-text-heading);">{{ $post->author?->name ?? '—' }}</h4>
                            <p class="text-sm mt-1" style="color: var(--color-text-muted);">Writer at XenonBlog. Sharing insights on technology, design, and everything in between.</p>
                        </div>
                    </div>
                </article>
            </div>
        </div>

        {{-- Related Articles --}}
        @if($relatedPosts->isNotEmpty())
        <section class="py-16" style="background-color: var(--color-surface-elevated);">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold mb-8" style="color: var(--color-text-heading);">Related articles</h2>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($relatedPosts as $related)
                    <article class="group rounded-2xl overflow-hidden transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
                        @if($related->featured_image)
                        <a href="{{ route('blog.show', $related->slug) }}" class="aspect-[16/9] overflow-hidden block">
                            <img src="{{ $related->featured_image }}" alt="{{ $related->title }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                        </a>
                        @endif
                        <div class="p-5">
                            @if($related->category)
                            <span class="text-xs font-semibold uppercase tracking-wider" style="color: var(--color-primary-600);">{{ $related->category->name }}</span>
                            @endif
                            <h3 class="text-base font-bold mt-1 leading-snug" style="color: var(--color-text-heading);">
                                <a href="{{ route('blog.show', $related->slug) }}" class="hover:underline decoration-2 decoration-[var(--color-primary-500)] underline-offset-2">{{ $related->title }}</a>
                            </h3>
                            <p class="text-xs mt-2" style="color: var(--color-text-muted);">{{ $related->published_at?->format('M d, Y') }} · {{ $related->reading_time }} min</p>
                        </div>
                    </article>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- Comments --}}
        <section class="py-16" style="background-color: var(--color-surface);">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="space-y-8">
                    @include('partials.comments', ['post' => $post])
                </div>
            </div>
        </section>

        <x-newsletter-section />

    </main>

    <x-public-footer :categories="$categories ?? []" />

    <script>
        // Reading progress bar
        window.addEventListener('scroll', function() {
            var winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            var scrolled = (winScroll / height) * 100;
            document.getElementById('progressFill').style.width = scrolled + '%';
        });
    </script>
</body>
</html>
