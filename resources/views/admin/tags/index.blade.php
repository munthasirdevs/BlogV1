<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--color-text-heading)">
                {{ __('Tags') }}
            </h2>
            <a href="{{ route('admin.tags.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">
                {{ __('New Tag') }}
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

                    <table class="min-w-full divide-y" style="border-color: var(--color-border)">
                        <thead style="background-color: var(--color-surface-elevated)">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Slug') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Usage Count') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color: var(--color-border); background-color: var(--color-surface-card)">
                            @forelse($tags as $tag)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            @if($tag->color)
                                                <span class="inline-block h-4 w-4 rounded-full" style="background-color: {{ $tag->color }}"></span>
                                            @endif
                                            <div class="text-sm font-medium" style="color: var(--color-text-heading)">{{ $tag->name }}</div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="text-sm" style="color: var(--color-text-muted)">{{ $tag->slug }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm" style="color: var(--color-text-muted)">
                                        {{ $tag->usage_count }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 {{ $tag->status === 'active' ? 'text-green-800' : 'bg-gray-100 text-gray-800' }}" style="{{ $tag->status === 'active' ? 'background-color: var(--color-success-bg)' : '' }}">
                                            {{ ucfirst($tag->status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                        <a href="{{ route('admin.tags.edit', $tag) }}" class="hover:text-indigo-900" style="color: var(--color-primary-600)">{{ __('Edit') }}</a>
                                        <form action="{{ route('admin.tags.destroy', $tag) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ml-2 hover:text-red-900" style="color: var(--color-error)" onclick="return confirm('{{ __('Are you sure?') }}')">{{ __('Delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm" style="color: var(--color-text-muted)">{{ __('No tags found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $tags->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
