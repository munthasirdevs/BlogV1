<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--color-text-heading)">
                {{ __('Categories') }}
            </h2>
            <a href="{{ route('admin.categories.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">
                {{ __('New Category') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-md p-4 text-sm" style="background-color: var(--color-success-bg); color: var(--color-success)">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-4 flex items-center gap-4">
                <form method="GET" action="{{ route('admin.categories.index') }}" class="flex items-center gap-2">
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Search categories...') }}" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>{{ __('Published') }}</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                        <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>{{ __('Archived') }}</option>
                        <option value="hidden" {{ request('status') === 'hidden' ? 'selected' : '' }}>{{ __('Hidden') }}</option>
                    </select>
                    <button type="submit" class="inline-flex items-center rounded-md px-3 py-2 text-xs font-semibold text-white" style="background-color: var(--color-primary-600)">{{ __('Filter') }}</button>
                    @if(request()->anyFilled(['search', 'status']))
                    <a href="{{ route('admin.categories.index') }}" class="text-xs" style="color: var(--color-text-muted)">{{ __('Clear') }}</a>
                    @endif
                </form>
            </div>

            <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                <div class="p-6" style="color: var(--color-text-heading)">
                    @if($tree->isNotEmpty())
                    <div class="space-y-1">
                        @foreach($tree as $parent)
                        <div class="rounded-lg border" style="border-color: var(--color-border);">
                            <div class="flex items-center justify-between px-4 py-3 hover:bg-gray-50" style="background-color: var(--color-surface-elevated);">
                                <div class="flex items-center gap-3">
                                    @if($parent->color)
                                    <span class="w-3 h-3 rounded-full" style="background-color: {{ $parent->color }}"></span>
                                    @endif
                                    <div>
                                        <a href="{{ route('admin.categories.edit', $parent) }}" class="font-medium hover:underline" style="color: var(--color-text-heading)">{{ $parent->name }}</a>
                                        <span class="text-xs ml-2" style="color: var(--color-text-muted)">/{{ $parent->slug }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 text-sm" style="color: var(--color-text-muted)">
                                    <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 {{ $parent->status === 'published' ? 'text-green-800' : ($parent->status === 'draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}" style="{{ $parent->status === 'published' ? 'background-color: var(--color-success-bg)' : '' }}">
                                        {{ ucfirst($parent->status) }}
                                    </span>
                                    <span>{{ $parent->posts_count ?? $parent->article_count }} {{ __('posts') }}</span>
                                    <a href="{{ route('admin.categories.edit', $parent) }}" class="hover:text-indigo-900" style="color: var(--color-primary-600)">{{ __('Edit') }}</a>
                                </div>
                            </div>
                            @if($parent->children->isNotEmpty())
                            <div class="divide-y" style="border-color: var(--color-border);">
                                @foreach($parent->children as $child)
                                <div class="flex items-center justify-between px-4 py-2.5 pl-12 hover:bg-gray-50">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-4 h-4" style="color: var(--color-text-disabled);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                        <a href="{{ route('admin.categories.edit', $child) }}" class="hover:underline" style="color: var(--color-text-body)">{{ $child->name }}</a>
                                        <span class="text-xs" style="color: var(--color-text-muted)">/{{ $child->slug }}</span>
                                    </div>
                                    <div class="flex items-center gap-4 text-sm" style="color: var(--color-text-muted)">
                                        <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 {{ $child->status === 'published' ? 'text-green-800' : ($child->status === 'draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}" style="{{ $child->status === 'published' ? 'background-color: var(--color-success-bg)' : '' }}">
                                            {{ ucfirst($child->status) }}
                                        </span>
                                        <span>{{ $child->posts_count ?? $child->article_count }} posts</span>
                                        <a href="{{ route('admin.categories.edit', $child) }}" class="hover:text-indigo-900" style="color: var(--color-primary-600)">{{ __('Edit') }}</a>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-12">
                        <p class="text-sm" style="color: var(--color-text-muted)">{{ __('No categories found.') }}</p>
                        <a href="{{ route('admin.categories.create') }}" class="mt-2 inline-flex items-center text-sm hover:text-indigo-900" style="color: var(--color-primary-600)">{{ __('Create your first category') }}</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
