<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $category->seo?->meta_title ?? ($category->name . ' — ' . config('app.name')) }}</title>
    <meta name="description" content="{{ $category->seo?->meta_description ?? $category->short_description ?? 'Explore articles about ' . $category->name }}">

    @if($category->seo)
    <meta name="keywords" content="{{ $category->seo->meta_keywords }}">
    <link rel="canonical" href="{{ $category->seo->canonical_url ?? route('category.show', $category->slug) }}">
    <meta name="robots" content="{{ $category->seo->robots_directive ?? 'index,follow' }}">
    <meta property="og:title" content="{{ $category->seo->og_title ?? $category->name }}">
    <meta property="og:description" content="{{ $category->seo->og_description ?? $category->short_description }}">
    @if($category->seo->og_image ?? $category->image)
    <meta property="og:image" content="{{ $category->seo->og_image ?? $category->image }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $category->seo->twitter_title ?? $category->seo->og_title ?? $category->name }}">
    <meta name="twitter:description" content="{{ $category->seo->twitter_description ?? $category->seo->meta_description ?? $category->short_description }}">
    @if($category->seo->og_image ?? $category->image)
    <meta name="twitter:image" content="{{ $category->seo->og_image ?? $category->image }}">
    @endif
    @endif

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

    @if($category->seo?->schema_type ?? true)
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "CollectionPage",
        "name": "{{ $category->name }}",
        "description": "{{ $category->short_description ?? 'Articles in ' . $category->name }}",
        "url": "{{ route('category.show', $category->slug) }}",
        "about": { "@type": "Thing", "name": "{{ $category->name }}" }
    }
    </script>
    @endif
</head>
<body class="font-sans antialiased" style="background-color: var(--color-surface); color: var(--color-text-body); padding-top: 64px;">

    <x-public-header active="category" />

    <main>
        {{-- Hero --}}
        @if($category->image)
        <div class="h-48 lg:h-72 overflow-hidden relative">
            <img src="{{ $category->image }}" alt="{{ $category->name }}" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 p-8 flex items-center gap-4">
                @if($category->icon)<span class="text-5xl">{{ $category->icon }}</span>@endif
                <div>
                    <h1 class="text-3xl sm:text-4xl font-bold text-white">{{ $category->name }}</h1>
                    @if($category->short_description)<p class="text-white/80 mt-1">{{ $category->short_description }}</p>@endif
                </div>
            </div>
        </div>
        @else
        <div class="py-12" style="background: linear-gradient(165deg, #0f172a, #1e293b);">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <x-breadcrumbs :items="[__('Home') => route('blog.index'), $category->name => '']" />
                <div class="flex items-center gap-4 mt-4">
                    @if($category->icon)<span class="text-5xl">{{ $category->icon }}</span>@endif
                    <h1 class="text-3xl sm:text-4xl font-bold text-white">{{ $category->name }}</h1>
                </div>
                @if($category->short_description)<p class="text-white/80 mt-2">{{ $category->short_description }}</p>@endif
            </div>
        </div>
        @endif

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            {{-- Breadcrumbs --}}
            @if($category->image)
            <x-breadcrumbs :items="[__('Home') => route('blog.index'), $category->name => '']" />
            @endif

            {{-- Sibling Categories --}}
            @if($category->parent && $siblingCategories->isNotEmpty())
            <div class="mb-8">
                <span class="text-xs font-semibold uppercase tracking-wider" style="color: var(--color-text-muted);">{{ __('Related Categories') }}</span>
                <div class="flex flex-wrap gap-2 mt-2">
                    @foreach($siblingCategories as $sib)
                    <a href="{{ route('category.show', $sib->slug) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors duration-200 hover:bg-gray-100"
                       style="background-color: var(--color-surface-elevated); color: var(--color-text-body); border: 1px solid var(--color-border); {{ $sib->id === $category->id ? 'border-indigo-500 font-bold' : '' }}">
                        @if($sib->color)<span class="w-2 h-2 rounded-full" style="background-color: {{ $sib->color }}"></span>@endif
                        {{ $sib->name }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Subcategories --}}
            @if($category->children->isNotEmpty())
            <div class="mt-8 mb-12">
                <h2 class="text-xl font-bold mb-4" style="color: var(--color-text-heading);">{{ __('Subcategories') }}</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($category->children as $child)
                    <a href="{{ route('category.show', $child->slug) }}"
                       class="p-4 rounded-xl border transition-all duration-200 hover:shadow-md hover:-translate-y-0.5"
                       style="border-color: var(--color-border); background-color: var(--color-surface-card);">
                        @if($child->icon)<span class="text-xl">{{ $child->icon }}</span>@endif
                        <h3 class="font-semibold" style="color: var(--color-text-heading);">{{ $child->name }}</h3>
                        <p class="text-sm mt-1" style="color: var(--color-text-muted);">{{ $child->article_count }} {{ __('articles') }}</p>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Featured Articles --}}
            @if($featuredPosts->isNotEmpty())
            <section class="mb-12">
                <h2 class="text-xl font-bold mb-4" style="color: var(--color-text-heading);">{{ __('Featured') }}</h2>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($featuredPosts as $fp)
                    <article class="group rounded-2xl overflow-hidden transition-all duration-300 hover:shadow-lg"
                             style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
                        <a href="{{ route('blog.show', $fp->slug) }}" class="aspect-[16/9] overflow-hidden block">
                            <img src="{{ $fp->featured_image ?? '' }}" alt="{{ $fp->title }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" onerror="this.parentElement.innerHTML='<div class=w-full+h-full+flex+items-center+justify-center style=background:var(--color-surface-elevated)><span class=text-4xl+font-bold+opacity-20>{{ substr($fp->title,0,1) }}</span></div>'">
                        </a>
                        <div class="p-4">
                            <h3 class="font-bold leading-snug" style="color: var(--color-text-heading);">
                                <a href="{{ route('blog.show', $fp->slug) }}" class="hover:underline">{{ $fp->title }}</a>
                            </h3>
                            <p class="text-xs mt-1" style="color: var(--color-text-muted);">{{ $fp->published_at?->format('M d, Y') }}</p>
                        </div>
                    </article>
                    @endforeach
                </div>
            </section>
            @endif

            {{-- Filter Bar --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <h2 class="text-2xl font-bold" style="color: var(--color-text-heading);">{{ __('Articles') }}</h2>
                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <select name="sort" onchange="this.form.submit()"
                            class="rounded-lg text-sm border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>{{ __('Latest') }}</option>
                        <option value="popular" {{ request('sort') === 'popular' ? 'selected' : '' }}>{{ __('Most Popular') }}</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>{{ __('Oldest') }}</option>
                    </select>
                    <input type="text" name="author" value="{{ request('author') }}" placeholder="{{ __('Author...') }}"
                           class="rounded-lg text-sm border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 w-32">
                    <input type="date" name="from" value="{{ request('from') }}"
                           class="rounded-lg text-sm border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <input type="date" name="to" value="{{ request('to') }}"
                           class="rounded-lg text-sm border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="submit" class="inline-flex items-center rounded-md px-3 py-1.5 text-xs font-semibold text-white" style="background-color: var(--color-primary-600)">{{ __('Filter') }}</button>
                    @if(request()->anyFilled(['sort', 'author', 'from', 'to']))
                    <a href="{{ route('category.show', $category->slug) }}" class="text-xs" style="color: var(--color-text-muted)">{{ __('Clear') }}</a>
                    @endif
                </form>
            </div>

            {{-- Article Grid --}}
            @if($posts->isNotEmpty())
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($posts as $post)
                <article class="group rounded-2xl overflow-hidden transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
                         style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
                    @if($post->featured_image)
                    <a href="{{ route('blog.show', $post->slug) }}" class="aspect-[16/9] overflow-hidden block">
                        <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                    </a>
                    @endif
                    <div class="p-5">
                        <h3 class="text-lg font-bold leading-snug" style="color: var(--color-text-heading);">
                            <a href="{{ route('blog.show', $post->slug) }}" class="hover:underline">{{ $post->title }}</a>
                        </h3>
                        @if($post->excerpt)
                        <p class="mt-2 text-sm line-clamp-2" style="color: var(--color-text-muted);">{{ Str::limit($post->excerpt, 120) }}</p>
                        @endif
                        <div class="mt-4 pt-4 flex items-center justify-between text-xs" style="border-top: 1px solid var(--color-border); color: var(--color-text-muted);">
                            <span>{{ $post->published_at?->format('M d, Y') }}</span>
                            <span>{{ $post->reading_time }} min read</span>
                        </div>
                    </div>
                </article>
                @endforeach
            </div>
            <div class="mt-8">
                {{ $posts->links() }}
            </div>
            @else
            <div class="text-center py-20">
                <p class="text-lg" style="color: var(--color-text-muted);">{{ __('No articles in this category yet.') }}</p>
            </div>
            @endif
        </div>

        <x-newsletter-section />
    </main>

    <x-public-footer :categories="$categories ?? []" />
</body>
</html>
