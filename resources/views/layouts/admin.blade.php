<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin - BlogV1')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
        <!-- Admin Top Nav -->
        <nav class="bg-white border-b border-gray-200 fixed top-0 left-0 right-0 z-30 h-16">
            <div class="px-4 flex items-center justify-between h-16">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-md hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <a href="{{ route('admin.dashboard') }}" class="text-xl font-bold text-gray-800">BlogV1</a>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">View Site</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-gray-600 hover:text-gray-900">Logout</button>
                    </form>
                </div>
            </div>
        </nav>

        <!-- Sidebar -->
        <aside class="fixed top-16 left-0 bottom-0 w-64 bg-white border-r border-gray-200 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 z-20" :class="{ 'translate-x-0': sidebarOpen }">
            <nav class="p-4 space-y-1 overflow-y-auto h-full">
                <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700' }}">Dashboard</a>
                <a href="{{ route('admin.posts.index') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 {{ request()->routeIs('admin.posts.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700' }}">Posts</a>
                <a href="{{ route('admin.categories.index') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 {{ request()->routeIs('admin.categories.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700' }}">Categories</a>
                <a href="{{ route('admin.tags.index') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 {{ request()->routeIs('admin.tags.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700' }}">Tags</a>
                <a href="{{ route('admin.media.index') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 {{ request()->routeIs('admin.media.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700' }}">Media</a>
                <a href="{{ route('admin.comments.index') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 {{ request()->routeIs('admin.comments.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700' }}">Comments</a>
                <a href="{{ route('admin.users.index') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 {{ request()->routeIs('admin.users.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700' }}">Users</a>
                <div class="border-t my-2"></div>
                <a href="{{ route('admin.seo.index') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 {{ request()->routeIs('admin.seo.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700' }}">SEO</a>
                <a href="{{ route('admin.ai.index') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 {{ request()->routeIs('admin.ai.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700' }}">AI Tools</a>
                <a href="{{ route('admin.analytics') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 {{ request()->routeIs('admin.analytics') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700' }}">Analytics</a>
                <a href="{{ route('admin.settings.index') }}" class="block px-4 py-2 text-sm rounded-md hover:bg-gray-100 {{ request()->routeIs('admin.settings.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700' }}">Settings</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="pt-16 lg:pl-64">
            <div class="p-6">
                @if (session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>
    @stack('scripts')
</body>
</html>
