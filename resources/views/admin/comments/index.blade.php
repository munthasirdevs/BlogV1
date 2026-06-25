<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Comments') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-4 flex items-center gap-2">
                <a href="{{ route('admin.comments.index') }}" class="inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium {{ $status === 'all' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    {{ __('All') }}
                </a>
                <a href="{{ route('admin.comments.index', ['status' => 'pending']) }}" class="inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium {{ $status === 'pending' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    {{ __('Pending') }}
                </a>
                <a href="{{ route('admin.comments.index', ['status' => 'approved']) }}" class="inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium {{ $status === 'approved' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    {{ __('Approved') }}
                </a>
                <a href="{{ route('admin.comments.index', ['status' => 'spam']) }}" class="inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium {{ $status === 'spam' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    {{ __('Spam') }}
                </a>
                <a href="{{ route('admin.comments.index', ['status' => 'trash']) }}" class="inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium {{ $status === 'trash' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    {{ __('Trash') }}
                </a>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-6 text-gray-900">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Post') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Commenter') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Comment') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Date') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($comments as $comment)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ Str::limit($comment->post?->title ?? '—', 40) }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $comment->user?->name ?? $comment->guest_name ?? __('Anonymous') }}
                                        </div>
                                        @if($comment->guest_email)
                                            <div class="text-xs text-gray-500">{{ $comment->guest_email }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-500">{{ Str::limit($comment->body, 80) }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5
                                            {{ $comment->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $comment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $comment->status === 'spam' ? 'bg-red-100 text-red-800' : '' }}
                                            {{ $comment->status === 'trash' ? 'bg-gray-100 text-gray-800' : '' }}">
                                            {{ ucfirst($comment->status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {{ $comment->created_at->format('M d, Y H:i') }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                        @if($comment->status !== 'approved')
                                            <form action="{{ route('admin.comments.approve', $comment) }}" method="POST" class="inline-block">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-900">{{ __('Approve') }}</button>
                                            </form>
                                        @endif
                                        @if($comment->status !== 'spam')
                                            <form action="{{ route('admin.comments.spam', $comment) }}" method="POST" class="inline-block">
                                                @csrf
                                                <button type="submit" class="ml-2 text-red-600 hover:text-red-900">{{ __('Spam') }}</button>
                                            </form>
                                        @endif
                                        @if($comment->status !== 'trash')
                                            <form action="{{ route('admin.comments.reject', $comment) }}" method="POST" class="inline-block">
                                                @csrf
                                                <button type="submit" class="ml-2 text-gray-600 hover:text-gray-900">{{ __('Reject') }}</button>
                                            </form>
                                        @endif
                                        <form action="{{ route('admin.comments.destroy', $comment) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ml-2 text-red-600 hover:text-red-900" onclick="return confirm('{{ __('Are you sure?') }}')">{{ __('Delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">{{ __('No comments found.') }}</td>
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
