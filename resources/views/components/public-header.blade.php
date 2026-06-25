@props(['active' => 'home'])

<header x-data="{
    scrolled: false,
    mobileOpen: false,
    searchOpen: false,
    init() {
        window.addEventListener('scroll', () => { this.scrolled = window.scrollY > 20; });
    }
}" :class="scrolled ? 'shadow-lg border-b' : 'border-b border-transparent'" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300" style="background-color: rgba(255,255,255,0.95); border-color: var(--color-border); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);" x-init="init()">

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">

            {{-- Left: Logo --}}
            <div class="flex items-center gap-8">
                <a href="{{ route('blog.index') }}" class="flex items-center gap-1.5 shrink-0">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-sm" style="background: linear-gradient(135deg, var(--color-primary-600), var(--color-primary-400));">X</span>
                    <span class="text-lg font-bold hidden sm:block" style="color: var(--color-text-heading);">XenonBlog</span>
                </a>

                {{-- Desktop Nav --}}
                <nav class="hidden lg:flex items-center gap-1">
                    <a href="{{ route('blog.index') }}" class="px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200" :class="'{{ $active }}' === 'home' ? 'text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100'" :style="'{{ $active }}' === 'home' ? 'background-color: var(--color-primary-600);' : ''">{{ __('Home') }}</a>
                    <a href="{{ route('blog.index') }}" class="px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200" :class="'{{ $active }}' === 'blog' ? 'text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100'" :style="'{{ $active }}' === 'blog' ? 'background-color: var(--color-primary-600);' : ''">{{ __('Blog') }}</a>
                    <a href="{{ route('about') }}" class="px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200" :class="'{{ $active }}' === 'about' ? 'text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100'" :style="'{{ $active }}' === 'about' ? 'background-color: var(--color-primary-600);' : ''">{{ __('About') }}</a>
                    <a href="{{ route('contact') }}" class="px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200" :class="'{{ $active }}' === 'contact' ? 'text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100'" :style="'{{ $active }}' === 'contact' ? 'background-color: var(--color-primary-600);' : ''">{{ __('Contact') }}</a>
                </nav>
            </div>

            {{-- Right: Actions --}}
            <div class="flex items-center gap-1 sm:gap-2">

                {{-- Search (Desktop) --}}
                <button @click="searchOpen = !searchOpen" class="hidden sm:inline-flex p-2 rounded-lg transition-colors duration-200" style="color: var(--color-text-muted);" aria-label="Search">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>

                {{-- Theme Toggle --}}
                <x-theme-toggle />

                {{-- Notifications (Desktop, Auth Only) --}}
                @auth
                <button class="hidden sm:inline-flex p-2 rounded-lg transition-colors duration-200 relative" style="color: var(--color-text-muted);" aria-label="Notifications">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span class="absolute -top-0.5 -right-0.5 w-4 h-4 rounded-full text-[10px] font-bold flex items-center justify-center text-white" style="background-color: var(--color-error);">3</span>
                </button>
                @endauth

                {{-- User Menu (Desktop, Auth) --}}
                @auth
                <div class="hidden sm:flex items-center gap-2 pl-2" style="border-left: 1px solid var(--color-border);">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white" style="background: linear-gradient(135deg, var(--color-primary-600), var(--color-primary-400));">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <a href="{{ route('dashboard') }}" class="text-sm font-medium hidden md:block" style="color: var(--color-text-body);">{{ Auth::user()->name }}</a>
                </div>
                @else
                {{-- CTA Button (Desktop, Guest) --}}
                <a href="{{ route('login') }}" class="hidden sm:inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg text-white transition-all duration-200 hover:opacity-90" style="background: linear-gradient(135deg, var(--color-primary-600), var(--color-primary-400));">
                    {{ __('Sign In') }}
                </a>
                @endauth

                {{-- Mobile: Search + Hamburger --}}
                <button @click="searchOpen = !searchOpen" class="sm:hidden p-2 rounded-lg transition-colors duration-200" style="color: var(--color-text-muted);" aria-label="Search">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>
                <button @click="mobileOpen = !mobileOpen" class="sm:hidden p-2 rounded-lg transition-colors duration-200" style="color: var(--color-text-muted);" aria-label="Menu">
                    <svg x-show="!mobileOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="mobileOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Search Overlay --}}
    <div x-show="searchOpen" x-cloak @click.away="searchOpen = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="border-t" style="background-color: var(--color-surface); border-color: var(--color-border);">
        <div class="mx-auto max-w-2xl px-4 py-4">
            <form action="{{ route('search') }}" method="GET" role="search">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5" style="color: var(--color-text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="search" name="q" placeholder="Search articles, topics, authors..." class="w-full pl-10 pr-4 py-3 rounded-xl text-sm focus:outline-none focus:ring-2 transition-all" style="background-color: var(--color-surface-elevated); border: 1px solid var(--color-border); color: var(--color-text-body);" autocomplete="off" x-ref="searchInput">
                </div>
            </form>
        </div>
    </div>

    {{-- Mobile Menu Overlay --}}
    <div x-show="mobileOpen" x-cloak @click.away="mobileOpen = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="border-t lg:hidden" style="background-color: var(--color-surface); border-color: var(--color-border);">
        <div class="mx-auto max-w-7xl px-4 py-4 space-y-1">
            <a href="{{ route('blog.index') }}" class="block px-4 py-3 rounded-lg text-sm font-medium transition-colors" :class="'{{ $active }}' === 'home' ? 'text-white' : 'text-gray-700 dark:text-gray-300'" :style="'{{ $active }}' === 'home' ? 'background-color: var(--color-primary-600);' : ''">{{ __('Home') }}</a>
            <a href="{{ route('blog.index') }}" class="block px-4 py-3 rounded-lg text-sm font-medium transition-colors" :class="'{{ $active }}' === 'blog' ? 'text-white' : 'text-gray-700 dark:text-gray-300'" :style="'{{ $active }}' === 'blog' ? 'background-color: var(--color-primary-600);' : ''">{{ __('Blog') }}</a>
            <a href="{{ route('about') }}" class="block px-4 py-3 rounded-lg text-sm font-medium transition-colors" :class="'{{ $active }}' === 'about' ? 'text-white' : 'text-gray-700 dark:text-gray-300'" :style="'{{ $active }}' === 'about' ? 'background-color: var(--color-primary-600);' : ''">{{ __('About') }}</a>
            <a href="{{ route('contact') }}" class="block px-4 py-3 rounded-lg text-sm font-medium transition-colors" :class="'{{ $active }}' === 'contact' ? 'text-white' : 'text-gray-700 dark:text-gray-300'" :style="'{{ $active }}' === 'contact' ? 'background-color: var(--color-primary-600);' : ''">{{ __('Contact') }}</a>
            <hr class="my-2" style="border-color: var(--color-border);">
            @auth
            <div class="px-4 py-3 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white" style="background: linear-gradient(135deg, var(--color-primary-600), var(--color-primary-400));">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                <div>
                    <p class="text-sm font-medium" style="color: var(--color-text-heading);">{{ Auth::user()->name }}</p>
                    <a href="{{ route('dashboard') }}" class="text-xs" style="color: var(--color-primary-600);">{{ __('Dashboard') }}</a>
                </div>
            </div>
            @else
            <a href="{{ route('login') }}" class="block w-full text-center px-4 py-3 rounded-lg text-sm font-medium text-white transition-all" style="background: linear-gradient(135deg, var(--color-primary-600), var(--color-primary-400));">{{ __('Sign In') }}</a>
            <a href="{{ route('register') }}" class="block w-full text-center px-4 py-3 rounded-lg text-sm font-medium mt-2 transition-all" style="color: var(--color-primary-600); border: 1px solid var(--color-primary-600);">{{ __('Get Started') }}</a>
            @endauth
        </div>
    </div>

</header>
