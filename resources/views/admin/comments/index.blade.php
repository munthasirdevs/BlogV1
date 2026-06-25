<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--color-text-heading)">
                {{ __('Comments') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-md p-4 text-sm" style="background-color: var(--color-success-bg); color: var(--color-success)">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-4 flex items-center gap-2">
                <a href="{{ route('admin.comments.index') }}" class="inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium {{ $status === 'all' ? 'text-indigo-700' : 'text-gray-700 hover:bg-gray-200' }}" style="{{ $status === 'all' ? 'background-color: var(--color-primary-50); color: var(--color-primary-600)' : 'background-color: var(--color-surface-elevated)' }}">
                    {{ __('All') }}
                </a>
                <a href="{{ route('admin.comments.index', ['status' => 'pending']) }}" class="inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium {{ $status === 'pending' ? 'text-indigo-700' : 'text-gray-700 hover:bg-gray-200' }}" style="{{ $status === 'pending' ? 'background-color: var(--color-primary-50); color: var(--color-primary-600)' : 'background-color: var(--color-surface-elevated)' }}">
                    {{ __('Pending') }}
                </a>
                <a href="{{ route('admin.comments.index', ['status' => 'approved']) }}" class="inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium {{ $status === 'approved' ? 'text-indigo-700' : 'text-gray-700 hover:bg-gray-200' }}" style="{{ $status === 'approved' ? 'background-color: var(--color-primary-50); color: var(--color-primary-600)' : 'background-color: var(--color-surface-elevated)' }}">
                    {{ __('Approved') }}
                </a>
                <a href="{{ route('admin.comments.index', ['status' => 'spam']) }}" class="inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium {{ $status === 'spam' ? 'text-indigo-700' : 'text-gray-700 hover:bg-gray-200' }}" style="{{ $status === 'spam' ? 'background-color: var(--color-primary-50); color: var(--color-primary-600)' : 'background-color: var(--color-surface-elevated)' }}">
                    {{ __('Spam') }}
                </a>
                <a href="{{ route('admin.comments.index', ['status' => 'trash']) }}" class="inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium {{ $status === 'trash' ? 'text-indigo-700' : 'text-gray-700 hover:bg-gray-200' }}" style="{{ $status === 'trash' ? 'background-color: var(--color-primary-50); color: var(--color-primary-600)' : 'background-color: var(--color-surface-elevated)' }}">
                    {{ __('Trash') }}
                </a>
            </div>

            <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                <div class="p-6" style="color: var(--color-text-heading)">
                    <table class="min-w-full divide-y" style="border-color: var(--color-border)">
                        <thead style="background-color: var(--color-surface-elevated)">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Post') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Commenter') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Comment') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Date') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color: var(--color-border); background-color: var(--color-surface-card)">
                            @forelse($comments as $comment)
                                <tr>
                                    <td class="px-6 py-4" style="color: var(--color-text-muted)">
                                        <div class="text-sm" style="color: var(--color-text-heading)">{{ Str::limit($comment->post?->title ?? '—', 40) }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="text-sm font-medium" style="color: var(--color-text-heading)">
                                            {{ $comment->user?->name ?? $comment->guest_name ?? __('Anonymous') }}
                                        </div>
                                        @if($comment->guest_email)
                                            <div class="text-xs" style="color: var(--color-text-muted)">{{ $comment->guest_email }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4" style="color: var(--color-text-muted)">
                                        <div class="text-sm" style="color: var(--color-text-muted)">{{ Str::limit($comment->body, 80) }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5
                                            {{ $comment->status === 'approved' ? 'text-green-800' : '' }}
                                            {{ $comment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $comment->status === 'spam' ? 'text-red-800' : '' }}
                                            {{ $comment->status === 'trash' ? 'bg-gray-100 text-gray-800' : '' }}" style="{{ $comment->status === 'approved' ? 'background-color: var(--color-success-bg)' : '' }} {{ $comment->status === 'spam' ? 'background-color: var(--color-error-bg)' : '' }}">
                                            {{ ucfirst($comment->status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm" style="color: var(--color-text-muted)">
                                        {{ $comment->created_at->format('M d, Y H:i') }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                        @if($comment->status !== 'approved')
                                            <form action="{{ route('admin.comments.approve', $comment) }}" method="POST" class="inline-block">
                                                @csrf
                                                <button type="submit" class="hover:text-green-900" style="color: var(--color-success)">{{ __('Approve') }}</button>
                                            </form>
                                        @endif
                                        @if($comment->status !== 'spam')
                                            <form action="{{ route('admin.comments.spam', $comment) }}" method="POST" class="inline-block">
                                                @csrf
                                                <button type="submit" class="ml-2 hover:text-red-900" style="color: var(--color-error)">{{ __('Spam') }}</button>
                                            </form>
                                        @endif
                                        @if($comment->status !== 'trash')
                                            <form action="{{ route('admin.comments.reject', $comment) }}" method="POST" class="inline-block">
                                                @csrf
                                                <button type="submit" class="ml-2 hover:text-gray-900" style="color: var(--color-text-body)">{{ __('Reject') }}</button>
                                            </form>
                                        @endif
                                        <form action="{{ route('admin.comments.destroy', $comment) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ml-2 hover:text-red-900" style="color: var(--color-error)" onclick="return confirm('{{ __('Are you sure?') }}')">{{ __('Delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-sm" style="color: var(--color-text-muted)">{{ __('No comments found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $comments->appends(['status' => $status])->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
