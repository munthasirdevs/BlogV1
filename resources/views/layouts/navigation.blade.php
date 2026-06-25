<nav x-data="{ open: false }" class="border-b" style="background-color: var(--color-surface-header); border-color: var(--color-border);">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current" style="color: var(--color-primary-600);" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                </div>
            </div>

            @auth
            <!-- Notification Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <div class="relative" x-data="{ open: false, unreadCount: 0, notifications: [] }" x-init="
                    fetch('{{ route('admin.notifications.count') }}')
                        .then(r => r.json())
                        .then(d => { unreadCount = d.count; });
                ">
                    <button @click="
                        open = !open;
                        if (open && notifications.length === 0) {
                            fetch('{{ route('admin.notifications.index') }}?limit=5')
                                .then(r => r.text())
                                .then(html => {
                                    let parser = new DOMParser();
                                    let doc = parser.parseFromString(html, 'text/html');
                                    let items = doc.querySelectorAll('.notification-item');
                                    notifications = Array.from(items).map(item => ({
                                        id: item.dataset.id,
                                        title: item.dataset.title,
                                        message: item.dataset.message,
                                        time: item.dataset.time,
                                        read: item.dataset.read === 'true',
                                    }));
                                });
                        }
                    " class="relative inline-flex items-center p-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-500)] transition duration-150 ease-in-out" style="color: var(--color-text-muted);">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span x-show="unreadCount > 0" x-text="unreadCount" class="absolute -right-1 -top-1 inline-flex items-center justify-center h-5 w-5 rounded-full text-xs font-bold text-white" style="background-color: var(--color-error);"></span>
                    </button>

                    <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-80 rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50" style="display: none; background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
                        <div class="py-2">
                            <template x-if="notifications.length === 0">
                                <p class="px-4 py-3 text-sm" style="color: var(--color-text-muted);">{{ __('No new notifications') }}</p>
                            </template>
                            <template x-for="notification in notifications.slice(0, 5)" :key="notification.id">
                                <div class="px-4 py-3 border-b last:border-b-0" style="border-color: var(--color-border);">
                                    <p class="text-sm font-medium" style="color: var(--color-text-heading);" x-text="notification.title"></p>
                                    <p class="text-xs mt-1" style="color: var(--color-text-muted);" x-text="notification.message"></p>
                                    <p class="text-xs mt-1" style="color: var(--color-text-disabled);" x-text="notification.time"></p>
                                </div>
                            </template>
                            <div class="border-t mt-2 pt-2 px-4" style="border-color: var(--color-border);">
                                <a href="{{ route('admin.notifications.index') }}" class="text-sm font-medium" style="color: var(--color-primary-600);">
                                    {{ __('View All') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-500)] transition ease-in-out duration-150" style="color: var(--color-text-body); background-color: var(--color-surface);">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
            @endauth

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-500)] transition duration-150 ease-in-out" style="color: var(--color-text-muted);">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden" style="border-top: 1px solid var(--color-border);">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        @auth
        <div class="pt-4 pb-1 border-t" style="border-color: var(--color-border);">
            <div class="px-4">
                <div class="font-medium text-base" style="color: var(--color-text-heading);">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm" style="color: var(--color-text-muted);">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
        @endauth
    </div>
</nav>