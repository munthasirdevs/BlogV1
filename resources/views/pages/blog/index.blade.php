<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Blog') }} — {{ config('app.name', 'XenonBlog') }}</title>

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
</head>
<body class="font-sans antialiased" style="background-color: var(--color-surface); color: var(--color-text-body); padding-top: 64px;">

    <x-public-header active="home" />

    <main>
        <x-hero-section :trendingTopics="$tags" />
        <x-featured-posts :featuredPosts="$featuredPosts" />
        <x-trending-posts :trendingPosts="$trendingPosts" />
        <x-categories-grid :categories="$categories" />
        <x-latest-articles :posts="$posts" />
        <x-newsletter-section />
    </main>

    <x-public-footer :categories="$categories" />

</body>
</html>
