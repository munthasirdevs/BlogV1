<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Revision History: ') }} {{ $post->title }}
            </h2>
            <a href="{{ route('admin.posts.edit', $post) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">
                {{ __('Back to Post') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(isset($revision) && isset($diffs))
                        <div class="mb-6">
                            <h3 class="mb-4 text-lg font-semibold">
                                {{ __('Diff View - Revision #') }}{{ $revision->revision_number }}
                                <span class="ml-2 text-sm font-normal text-gray-500">
                                    {{ __('by') }} {{ $revision->editor?->name ?? '—' }}
                                    {{ $revision->created_at->diffForHumans() }}
                                </span>
                            </h3>

                            @foreach($diffs as $field => $diffLines)
                                <div class="mb-6">
                                    <h4 class="mb-2 text-sm font-semibold uppercase tracking-wider text-gray-600">
                                        {{ ucfirst($field) }}
                                    </h4>
                                    <div class="overflow-hidden rounded-lg border border-gray-200">
                                        <div class="grid grid-cols-2 divide-x divide-gray-200">
                                            <div class="bg-gray-50 px-4 py-2 text-xs font-semibold uppercase text-gray-500">
                                                {{ __('Revision #') }}{{ $revision->revision_number }}
                                            </div>
                                            <div class="bg-gray-50 px-4 py-2 text-xs font-semibold uppercase text-gray-500">
                                                {{ __('Current') }}
                                            </div>
                                        </div>
                                        @foreach($diffLines as $line)
                                            @if($line['type'] === 'unchanged')
                                                <div class="grid grid-cols-2 divide-x divide-gray-200">
                                                    <div class="bg-white px-4 py-1 text-sm text-gray-700">{{ $line['old'] ?? '&nbsp;' }}</div>
                                                    <div class="bg-white px-4 py-1 text-sm text-gray-700">{{ $line['new'] ?? '&nbsp;' }}</div>
                                                </div>
                                            @elseif($line['type'] === 'added')
                                                <div class="grid grid-cols-2 divide-x divide-gray-200">
                                                    <div class="bg-gray-100 px-4 py-1 text-sm text-gray-400 italic">{{ __('(empty)') }}</div>
                                                    <div class="bg-green-50 px-4 py-1 text-sm text-green-800">{{ $line['new'] }}</div>
                                                </div>
                                            @elseif($line['type'] === 'removed')
                                                <div class="grid grid-cols-2 divide-x divide-gray-200">
                                                    <div class="bg-red-50 px-4 py-1 text-sm text-red-800 line-through">{{ $line['old'] }}</div>
                                                    <div class="bg-gray-100 px-4 py-1 text-sm text-gray-400 italic">{{ __('(empty)') }}</div>
                                                </div>
                                            @elseif($line['type'] === 'modified')
                                                <div class="grid grid-cols-2 divide-x divide-gray-200">
                                                    <div class="bg-red-50 px-4 py-1 text-sm text-red-800">{{ $line['old'] }}</div>
                                                    <div class="bg-green-50 px-4 py-1 text-sm text-green-800">{{ $line['new'] }}</div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            <form action="{{ route('admin.posts.revisions.restore', [$post, $revision]) }}" method="POST" class="mt-4">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded-md bg-amber-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-amber-500" onclick="return confirm('{{ __('Restore this revision? Current content will be saved as a new revision.') }}')">
                                    {{ __('Restore This Revision') }}
                                </button>
                            </form>
                        </div>
                        <hr class="my-8">
                    @endif

                    <h3 class="mb-4 text-lg font-semibold">{{ __('All Revisions') }}</h3>

                    <div class="space-y-4">
                        @forelse($revisions as $rev)
                            <div class="flex items-center justify-between rounded-lg border border-gray-200 p-4">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600">
                                        #{{ $rev->revision_number }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $rev->change_summary ?? __('Revision #') . $rev->revision_number }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $rev->editor?->name ?? '—' }}
                                            &middot;
                                            {{ $rev->created_at->format('M d, Y g:i A') }}
                                            @if($rev->ai_generated)
                                                &middot;
                                                <span class="rounded bg-purple-100 px-1.5 py-0.5 text-xs font-medium text-purple-700">{{ __('AI') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.posts.revisions.show', [$post, $rev]) }}" class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">
                                        {{ __('View Diff') }}
                                    </a>
                                    @if(!isset($revision) || $revision->id !== $rev->id)
                                        <form action="{{ route('admin.posts.revisions.restore', [$post, $rev]) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-md bg-amber-600 px-3 py-1.5 text-xs font-semibold uppercase tracking-widest text-white hover:bg-amber-500" onclick="return confirm('{{ __('Restore this revision?') }}')">
                                                {{ __('Restore') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">{{ __('No revisions found.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
