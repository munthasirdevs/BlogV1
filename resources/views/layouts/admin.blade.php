<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin - XenonBlog')</title>

    {{-- Theme flash prevention --}}
    <script>
        (function() {
            var theme = localStorage.getItem('theme');
            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased" style="background-color: var(--color-surface-elevated); color: var(--color-text-body);">
    <div class="min-h-screen" style="background-color: var(--color-surface-elevated);" x-data="{ sidebarOpen: false }">
        <!-- Admin Top Nav -->
        <nav class="fixed top-0 left-0 right-0 z-30 h-16" style="background-color: var(--color-surface-header); border-bottom: 1px solid var(--color-border);">
            <div class="px-4 flex items-center justify-between h-16">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-md hover:bg-gray-100 dark:hover:bg-slate-800">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <a href="{{ route('admin.dashboard') }}" class="text-xl font-bold" style="color: var(--color-text-heading);">XenonBlog</a>
                </div>
                <div class="flex items-center gap-4">
                    <x-theme-toggle />
                    <a href="{{ route('dashboard') }}" class="text-sm" style="color: var(--color-text-muted);">View Site</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm" style="color: var(--color-text-muted);">Logout</button>
                    </form>
                </div>
            </div>
        </nav>

        <!-- Sidebar -->
        <aside class="fixed top-16 left-0 bottom-0 w-64 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 z-20" style="background-color: var(--color-surface-sidebar); border-right: 1px solid var(--color-border);" :class="{ 'translate-x-0': sidebarOpen }">
            <nav class="p-4 space-y-1 overflow-y-auto h-full">
                <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 dark:hover:bg-slate-800 {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-50 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300' : '' }}" style="color: var(--color-text-body);">Dashboard</a>
                <a href="{{ route('admin.posts.index') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 dark:hover:bg-slate-800 {{ request()->routeIs('admin.posts.*') ? 'bg-indigo-50 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300' : '' }}" style="color: var(--color-text-body);">Posts</a>
                <a href="{{ route('admin.categories.index') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 dark:hover:bg-slate-800 {{ request()->routeIs('admin.categories.*') ? 'bg-indigo-50 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300' : '' }}" style="color: var(--color-text-body);">Categories</a>
                <a href="{{ route('admin.tags.index') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 dark:hover:bg-slate-800 {{ request()->routeIs('admin.tags.*') ? 'bg-indigo-50 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300' : '' }}" style="color: var(--color-text-body);">Tags</a>
                <a href="{{ route('admin.media.index') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 dark:hover:bg-slate-800 {{ request()->routeIs('admin.media.*') ? 'bg-indigo-50 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300' : '' }}" style="color: var(--color-text-body);">Media</a>
                <a href="{{ route('admin.comments.index') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 dark:hover:bg-slate-800 {{ request()->routeIs('admin.comments.*') ? 'bg-indigo-50 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300' : '' }}" style="color: var(--color-text-body);">Comments</a>
                <a href="{{ route('admin.users.index') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 dark:hover:bg-slate-800 {{ request()->routeIs('admin.users.*') ? 'bg-indigo-50 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300' : '' }}" style="color: var(--color-text-body);">Users</a>
                <div class="border-t my-2" style="border-color: var(--color-border);"></div>
                <a href="{{ route('admin.seo.index') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 dark:hover:bg-slate-800 {{ request()->routeIs('admin.seo.*') ? 'bg-indigo-50 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300' : '' }}" style="color: var(--color-text-body);">SEO</a>
                <a href="{{ route('admin.ai.index') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 dark:hover:bg-slate-800 {{ request()->routeIs('admin.ai.*') ? 'bg-indigo-50 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300' : '' }}" style="color: var(--color-text-body);">AI Tools</a>
                <a href="{{ route('admin.analytics') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 dark:hover:bg-slate-800 {{ request()->routeIs('admin.analytics') ? 'bg-indigo-50 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300' : '' }}" style="color: var(--color-text-body);">Analytics</a>
                <a href="{{ route('admin.settings.index') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 dark:hover:bg-slate-800 {{ request()->routeIs('admin.settings.*') ? 'bg-indigo-50 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300' : '' }}" style="color: var(--color-text-body);">Settings</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="pt-16 lg:pl-64">
            <div class="p-6">
                @if (session('success'))
                    <div class="px-4 py-3 rounded mb-4" style="background-color: var(--color-success-bg); color: var(--color-success); border: 1px solid var(--color-success);">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="px-4 py-3 rounded mb-4" style="background-color: var(--color-error-bg); color: var(--color-error); border: 1px solid var(--color-error);">{{ session('error') }}</div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>
    @stack('scripts')
</body>
</html>
