<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">{{ __('Total Posts') }}</div>
                        <div class="mt-2 flex items-baseline">
                            <span class="text-3xl font-bold text-gray-900">{{ $stats['totalPosts'] }}</span>
                        </div>
                        <div class="mt-2 flex gap-4 text-xs text-gray-500">
                            <span class="text-green-600">{{ __('Published') }}: {{ $stats['publishedPosts'] }}</span>
                            <span class="text-yellow-600">{{ __('Drafts') }}: {{ $stats['draftPosts'] }}</span>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">{{ __('Categories') }}</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['totalCategories'] }}</div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">{{ __('Tags') }}</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['totalTags'] }}</div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">{{ __('Comments') }}</div>
                        <div class="mt-2 flex items-baseline">
                            <span class="text-3xl font-bold text-gray-900">{{ $stats['totalComments'] }}</span>
                        </div>
                        @if($stats['pendingComments'] > 0)
                            <div class="mt-2">
                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">
                                    {{ $stats['pendingComments'] }} {{ __('pending') }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">{{ __('Users') }}</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['totalUsers'] }}</div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">{{ __('Total Views') }}</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($stats['totalViews']) }}</div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('Recent Published Posts') }}</h3>
                </div>
                <div class="p-6">
                    @if($recentPosts->isNotEmpty())
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Title') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Category') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Author') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Published') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Views') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($recentPosts as $post)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $post->title }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-sm text-gray-500">{{ $post->category?->name ?? '—' }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-sm text-gray-500">{{ $post->author?->name ?? '—' }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {{ $post->published_at?->format('M d, Y') }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {{ $post->views_count ?? 0 }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-sm text-gray-500">{{ __('No published posts yet.') }}</p>
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
