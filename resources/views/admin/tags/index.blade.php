<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--color-text-heading)">{{ __('Tags') }}</h2>
            <div class="flex items-center gap-2">
                <form action="{{ route('admin.tags.recalculate-trending') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-md px-3 py-2 text-xs font-semibold uppercase tracking-widest" style="background-color: var(--color-surface-elevated); color: var(--color-text-body)">{{ __('Recalculate Trending') }}</button>
                </form>
                <a href="{{ route('admin.tags.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">{{ __('New Tag') }}</a>
            </div>
        </div>
    </x-slot>
    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if(session('success'))
            <div class="mb-4 rounded-md p-4 text-sm" style="background-color: var(--color-success-bg); color: var(--color-success)">{{ session('success') }}</div>
            @endif

            {{-- Stats Bar --}}
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="rounded-lg p-4 text-center" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
                    <div class="text-2xl font-bold" style="color: var(--color-text-heading)">{{ $totalTags }}</div>
                    <div class="text-xs" style="color: var(--color-text-muted)">{{ __('Total Tags') }}</div>
                </div>
                <div class="rounded-lg p-4 text-center" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
                    <div class="text-2xl font-bold" style="color: var(--color-text-heading)">{{ $activeTags }}</div>
                    <div class="text-xs" style="color: var(--color-text-muted)">{{ __('Active Tags') }}</div>
                </div>
                <div class="rounded-lg p-4 text-center" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
                    <div class="text-2xl font-bold" style="color: var(--color-text-heading)">{{ $totalTags - $activeTags }}</div>
                    <div class="text-xs" style="color: var(--color-text-muted)">{{ __('Hidden') }}</div>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                <div class="p-6" style="color: var(--color-text-heading)">
                    <form method="GET" action="{{ route('admin.tags.index') }}" class="mb-4 flex items-center gap-2">
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Search tags...') }}" class="rounded-md border-gray-300 shadow-sm text-sm">
                        <select name="status" class="rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="">{{ __('All') }}</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                            <option value="hidden" {{ request('status') === 'hidden' ? 'selected' : '' }}>{{ __('Hidden') }}</option>
                        </select>
                        <select name="sort" class="rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="usage" {{ request('sort') === 'usage' ? 'selected' : '' }}>{{ __('By Usage') }}</option>
                            <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>{{ __('By Name') }}</option>
                            <option value="trending" {{ request('sort') === 'trending' ? 'selected' : '' }}>{{ __('Trending') }}</option>
                            <option value="created" {{ request('sort') === 'created' ? 'selected' : '' }}>{{ __('Newest') }}</option>
                        </select>
                        <button type="submit" class="inline-flex items-center rounded-md px-3 py-2 text-xs font-semibold text-white" style="background-color: var(--color-primary-600)">{{ __('Filter') }}</button>
                        @if(request()->anyFilled(['search', 'status', 'sort']))
                        <a href="{{ route('admin.tags.index') }}" class="text-xs" style="color: var(--color-text-muted)">{{ __('Clear') }}</a>
                        @endif
                    </form>

                    <table class="min-w-full divide-y" style="border-color: var(--color-border)">
                        <thead style="background-color: var(--color-surface-elevated)">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Posts') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Trending') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color: var(--color-border); background-color: var(--color-surface-card)">
                            @forelse($tags as $tag)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        @if($tag->color)<span class="inline-block h-3 w-3 rounded-full" style="background-color: {{ $tag->color }}"></span>@endif
                                        <div class="text-sm font-medium" style="color: var(--color-text-heading)">{{ $tag->name }}</div>
                                        <span class="text-xs" style="color: var(--color-text-muted)">/{{ $tag->slug }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm" style="color: var(--color-text-muted)">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 rounded-full" style="width: {{ max(4, ($tag->usage_count / $maxUsage) * 100) }}px; background-color: var(--color-primary-500);"></div>
                                        <span>{{ $tag->usage_count }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm" style="color: var(--color-text-muted)">
                                    @if($tag->trending_score > 0)
                                    <span class="text-amber-600">{{ $tag->trending_score }}</span>
                                    @else
                                    <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 {{ $tag->status === 'active' ? 'text-green-800' : 'bg-gray-100 text-gray-800' }}" style="{{ $tag->status === 'active' ? 'background-color: var(--color-success-bg)' : '' }}">{{ ucfirst($tag->status) }}</span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                    <a href="{{ route('admin.tags.show', $tag) }}" class="hover:text-indigo-900" style="color: var(--color-text-muted)">{{ __('View') }}</a>
                                    <a href="{{ route('admin.tags.edit', $tag) }}" class="ml-2 hover:text-indigo-900" style="color: var(--color-primary-600)">{{ __('Edit') }}</a>
                                    <form action="{{ route('admin.tags.destroy', $tag) }}" method="POST" class="inline-block">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="ml-2 hover:text-red-900" style="color: var(--color-error)" onclick="return confirm('{{ __('Delete this tag?') }}')">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="px-6 py-4 text-center text-sm" style="color: var(--color-text-muted)">{{ __('No tags found.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-4">{{ $tags->links() }}</div>
                </div>
            </div>

            {{-- Merge Section --}}
            <div class="mt-6 overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                <div class="border-b px-6 py-4" style="border-color: var(--color-border)">
                    <h3 class="text-base font-semibold" style="color: var(--color-text-heading)">{{ __('Merge Tags') }}</h3>
                </div>
                <div class="p-6">
                    <form action="{{ route('admin.tags.merge') }}" method="POST" class="flex items-end gap-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium mb-1" style="color: var(--color-text-muted)">{{ __('Source Tag (will be deleted)') }}</label>
                            <select name="source_id" required class="rounded-md border-gray-300 shadow-sm text-sm">
                                <option value="">{{ __('Select tag...') }}</option>
                                @foreach($tags as $tag)
                                <option value="{{ $tag->id }}">{{ $tag->name }} ({{ $tag->usage_count }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1" style="color: var(--color-text-muted)">{{ __('Target Tag (will receive posts)') }}</label>
                            <select name="target_id" required class="rounded-md border-gray-300 shadow-sm text-sm">
                                <option value="">{{ __('Select tag...') }}</option>
                                @foreach($tags as $tag)
                                <option value="{{ $tag->id }}">{{ $tag->name }} ({{ $tag->usage_count }})</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="inline-flex items-center rounded-md bg-amber-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-amber-500" onclick="return confirm('{{ __('Merge tags? This cannot be undone.') }}')">{{ __('Merge') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
