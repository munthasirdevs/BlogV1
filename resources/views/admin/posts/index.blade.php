<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--color-text-heading)">
                {{ __('Posts') }}
            </h2>
            <a href="{{ route('admin.posts.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">
                {{ __('New Post') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow-sm sm:rounded-lg" style="background-color: var(--color-surface-card)">
                <div class="p-6" style="color: var(--color-text-heading)">
                    @if(session('success'))
                        <div class="mb-4 rounded-md p-4 text-sm" style="background-color: var(--color-success-bg); color: var(--color-success)">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="mb-4 flex items-center gap-4">
                        <input type="text" id="search" placeholder="{{ __('Search posts...') }}" class="block w-full max-w-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <select id="category-filter" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('All Categories') }}</option>
                        </select>
                        <select id="status-filter" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('All Statuses') }}</option>
                            <option value="draft">{{ __('Draft') }}</option>
                            <option value="published">{{ __('Published') }}</option>
                            <option value="scheduled">{{ __('Scheduled') }}</option>
                            <option value="archived">{{ __('Archived') }}</option>
                        </select>
                    </div>

                    <table class="min-w-full divide-y" style="border-color: var(--color-border)">
                        <thead style="background-color: var(--color-surface-elevated)">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Title') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Author') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Category') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Views') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Published') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color: var(--color-border); background-color: var(--color-surface-card)">
                            @forelse($posts as $post)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium" style="color: var(--color-text-heading)">{{ $post->title }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="text-sm" style="color: var(--color-text-muted)">{{ $post->author?->name ?? '—' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="text-sm" style="color: var(--color-text-muted)">{{ $post->category?->name ?? '—' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 {{ $post->status === 'published' ? 'text-green-800' : ($post->status === 'draft' ? 'bg-yellow-100 text-yellow-800' : ($post->status === 'scheduled' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800')) }}" style="{{ $post->status === 'published' ? 'background-color: var(--color-success-bg)' : '' }}">
                                            {{ ucfirst($post->status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm" style="color: var(--color-text-muted)">
                                        {{ $post->views_count ?? 0 }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm" style="color: var(--color-text-muted)">
                                        {{ $post->published_at ? $post->published_at->format('M d, Y') : '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                        <a href="{{ route('admin.posts.edit', $post) }}" class="hover:text-indigo-900" style="color: var(--color-primary-600)">{{ __('Edit') }}</a>
                                        <form action="{{ route('admin.posts.destroy', $post) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ml-2 hover:text-red-900" style="color: var(--color-error)" onclick="return confirm('{{ __('Are you sure?') }}')">{{ __('Delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-sm" style="color: var(--color-text-muted)">{{ __('No posts found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $posts->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
