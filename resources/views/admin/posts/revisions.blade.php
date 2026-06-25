<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--color-text-heading);">
                {{ __('Revision History: ') }} {{ $post->title }}
            </h2>
            <a href="{{ route('admin.posts.edit', $post) }}" class="inline-flex items-center rounded-md px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white" style="background-color: var(--color-primary-600);">
                {{ __('Back to Post') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow-sm sm:rounded-lg" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
                <div class="p-6" style="color: var(--color-text-body);">
                    @if(session('success'))
                        <div class="mb-4 rounded-md p-4 text-sm" style="background-color: var(--color-success-bg); color: var(--color-success);">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(isset($revision) && isset($diffs))
                        <div class="mb-6">
                            <h3 class="mb-4 text-lg font-semibold" style="color: var(--color-text-heading);">
                                {{ __('Diff View - Revision #') }}{{ $revision->revision_number }}
                                <span class="ml-2 text-sm font-normal" style="color: var(--color-text-muted);">
                                    {{ __('by') }} {{ $revision->editor?->name ?? '—' }}
                                    {{ $revision->created_at->diffForHumans() }}
                                </span>
                            </h3>

                            @foreach($diffs as $field => $diffLines)
                                <div class="mb-6">
                                    <h4 class="mb-2 text-sm font-semibold uppercase tracking-wider" style="color: var(--color-text-muted);">
                                        {{ ucfirst($field) }}
                                    </h4>
                                    <div class="overflow-hidden rounded-lg" style="border: 1px solid var(--color-border);">
                                        <div class="grid grid-cols-2 divide-x" style="border-color: var(--color-border);">
                                            <div class="px-4 py-2 text-xs font-semibold uppercase" style="color: var(--color-text-muted); background-color: var(--color-surface-elevated);">
                                                {{ __('Revision #') }}{{ $revision->revision_number }}
                                            </div>
                                            <div class="px-4 py-2 text-xs font-semibold uppercase" style="color: var(--color-text-muted); background-color: var(--color-surface-elevated);">
                                                {{ __('Current') }}
                                            </div>
                                        </div>
                                        @foreach($diffLines as $line)
                                            @if($line['type'] === 'unchanged')
                                                <div class="grid grid-cols-2 divide-x" style="border-color: var(--color-border);">
                                                    <div class="px-4 py-1 text-sm" style="background-color: var(--color-surface-card); color: var(--color-text-body);">{{ $line['old'] ?? '&nbsp;' }}</div>
                                                    <div class="px-4 py-1 text-sm" style="background-color: var(--color-surface-card); color: var(--color-text-body);">{{ $line['new'] ?? '&nbsp;' }}</div>
                                                </div>
                                            @elseif($line['type'] === 'added')
                                                <div class="grid grid-cols-2 divide-x" style="border-color: var(--color-border);">
                                                    <div class="px-4 py-1 text-sm italic" style="background-color: var(--color-surface-elevated); color: var(--color-text-disabled);">{{ __('(empty)') }}</div>
                                                    <div class="px-4 py-1 text-sm" style="background-color: var(--color-success-bg); color: var(--color-success);">{{ $line['new'] }}</div>
                                                </div>
                                            @elseif($line['type'] === 'removed')
                                                <div class="grid grid-cols-2 divide-x" style="border-color: var(--color-border);">
                                                    <div class="px-4 py-1 text-sm line-through" style="background-color: var(--color-error-bg); color: var(--color-error);">{{ $line['old'] }}</div>
                                                    <div class="px-4 py-1 text-sm italic" style="background-color: var(--color-surface-elevated); color: var(--color-text-disabled);">{{ __('(empty)') }}</div>
                                                </div>
                                            @elseif($line['type'] === 'modified')
                                                <div class="grid grid-cols-2 divide-x" style="border-color: var(--color-border);">
                                                    <div class="px-4 py-1 text-sm" style="background-color: var(--color-error-bg); color: var(--color-error);">{{ $line['old'] }}</div>
                                                    <div class="px-4 py-1 text-sm" style="background-color: var(--color-success-bg); color: var(--color-success);">{{ $line['new'] }}</div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            <form action="{{ route('admin.posts.revisions.restore', [$post, $revision]) }}" method="POST" class="mt-4">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded-md px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white" style="background-color: var(--color-warning);" onclick="return confirm('{{ __('Restore this revision? Current content will be saved as a new revision.') }}')">
                                    {{ __('Restore This Revision') }}
                                </button>
                            </form>
                        </div>
                        <hr class="my-8" style="border-color: var(--color-border);">
                    @endif

                    <h3 class="mb-4 text-lg font-semibold" style="color: var(--color-text-heading);">{{ __('All Revisions') }}</h3>

                    <div class="space-y-4">
                        @forelse($revisions as $rev)
                            <div class="flex items-center justify-between rounded-lg p-4" style="border: 1px solid var(--color-border);">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold" style="background-color: var(--color-primary-100); color: var(--color-primary-700);">
                                        #{{ $rev->revision_number }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium" style="color: var(--color-text-heading);">
                                            {{ $rev->change_summary ?? __('Revision #') . $rev->revision_number }}
                                        </div>
                                        <div class="text-xs" style="color: var(--color-text-muted);">
                                            {{ $rev->editor?->name ?? '—' }}
                                            &middot;
                                            {{ $rev->created_at->format('M d, Y g:i A') }}
                                            @if($rev->ai_generated)
                                                &middot;
                                                <span class="rounded px-1.5 py-0.5 text-xs font-medium" style="background-color: var(--color-info-bg); color: var(--color-info);">{{ __('AI') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.posts.revisions.show', [$post, $rev]) }}" class="rounded-md px-3 py-1.5 text-xs font-semibold uppercase tracking-widest text-white" style="background-color: var(--color-primary-600);">
                                        {{ __('View Diff') }}
                                    </a>
                                    @if(!isset($revision) || $revision->id !== $rev->id)
                                        <form action="{{ route('admin.posts.revisions.restore', [$post, $rev]) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-md px-3 py-1.5 text-xs font-semibold uppercase tracking-widest text-white" style="background-color: var(--color-warning);" onclick="return confirm('{{ __('Restore this revision?') }}')">
                                                {{ __('Restore') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-sm" style="color: var(--color-text-muted);">{{ __('No revisions found.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
