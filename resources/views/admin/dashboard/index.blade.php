<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight" style="color: var(--color-text-heading)">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                    <div class="p-6">
                        <div class="text-sm font-medium" style="color: var(--color-text-muted)">{{ __('Total Posts') }}</div>
                        <div class="mt-2 flex items-baseline">
                            <span class="text-3xl font-bold" style="color: var(--color-text-heading)">{{ $stats['totalPosts'] }}</span>
                        </div>
                        <div class="mt-2 flex gap-4 text-xs" style="color: var(--color-text-muted)">
                            <span class="" style="color: var(--color-success)">{{ __('Published') }}: {{ $stats['publishedPosts'] }}</span>
                            <span class="text-yellow-600">{{ __('Drafts') }}: {{ $stats['draftPosts'] }}</span>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                    <div class="p-6">
                        <div class="text-sm font-medium" style="color: var(--color-text-muted)">{{ __('Categories') }}</div>
                        <div class="mt-2 text-3xl font-bold" style="color: var(--color-text-heading)">{{ $stats['totalCategories'] }}</div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                    <div class="p-6">
                        <div class="text-sm font-medium" style="color: var(--color-text-muted)">{{ __('Tags') }}</div>
                        <div class="mt-2 text-3xl font-bold" style="color: var(--color-text-heading)">{{ $stats['totalTags'] }}</div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                    <div class="p-6">
                        <div class="text-sm font-medium" style="color: var(--color-text-muted)">{{ __('Comments') }}</div>
                        <div class="mt-2 flex items-baseline">
                            <span class="text-3xl font-bold" style="color: var(--color-text-heading)">{{ $stats['totalComments'] }}</span>
                        </div>
                        @if($stats['pendingComments'] > 0)
                            <div class="mt-2">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" style="background-color: var(--color-warning-bg); color: var(--color-warning)">
                                    {{ $stats['pendingComments'] }} {{ __('pending') }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                    <div class="p-6">
                        <div class="text-sm font-medium" style="color: var(--color-text-muted)">{{ __('Users') }}</div>
                        <div class="mt-2 text-3xl font-bold" style="color: var(--color-text-heading)">{{ $stats['totalUsers'] }}</div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                    <div class="p-6">
                        <div class="text-sm font-medium" style="color: var(--color-text-muted)">{{ __('Total Views') }}</div>
                        <div class="mt-2 text-3xl font-bold" style="color: var(--color-text-heading)">{{ number_format($stats['totalViews']) }}</div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                <div class="border-b px-6 py-4" style="border-color: var(--color-border)">
                    <h3 class="text-base font-semibold" style="color: var(--color-text-heading)">{{ __('Recent Published Posts') }}</h3>
                </div>
                <div class="p-6">
                    @if($recentPosts->isNotEmpty())
                        <table class="min-w-full divide-y" style="border-color: var(--color-border)">
                            <thead style="background-color: var(--color-surface-elevated)">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Title') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Category') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Author') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Published') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Views') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" style="border-color: var(--color-border); background-color: var(--color-surface-card)">
                                @foreach($recentPosts as $post)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium" style="color: var(--color-text-heading)">{{ $post->title }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4" style="color: var(--color-text-muted)">
                                            <div class="text-sm" style="color: var(--color-text-muted)">{{ $post->category?->name ?? '—' }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4" style="color: var(--color-text-muted)">
                                            <div class="text-sm" style="color: var(--color-text-muted)">{{ $post->author?->name ?? '—' }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm" style="color: var(--color-text-muted)">
                                            {{ $post->published_at?->format('M d, Y') }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm" style="color: var(--color-text-muted)">
                                            {{ $post->views_count ?? 0 }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-sm" style="color: var(--color-text-muted)">{{ __('No published posts yet.') }}</p>
                    @endif
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
                <a href="{{ route('admin.posts.create') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-6 py-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    {{ __('New Post') }}
                </a>
                <a href="{{ route('admin.categories.create') }}" class="inline-flex items-center justify-center rounded-lg bg-green-600 px-6 py-4 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                    {{ __('New Category') }}
                </a>
                <a href="{{ route('admin.comments.index', ['status' => 'pending']) }}" class="inline-flex items-center justify-center rounded-lg bg-yellow-600 px-6 py-4 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500">
                    {{ __('View Comments') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
