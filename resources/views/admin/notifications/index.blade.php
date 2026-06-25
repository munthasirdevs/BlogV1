<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Notifications') }}
            </h2>
            <form action="{{ route('admin.notifications.markAllRead') }}" method="POST" class="inline-block">
                @csrf
                <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">
                    {{ __('Mark All as Read') }}
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($notifications->isNotEmpty())
                        <div class="space-y-4">
                            @foreach($notifications as $notification)
                                <div class="flex items-start justify-between rounded-lg border border-gray-200 p-4 {{ $notification->read_at ? '' : 'bg-indigo-50' }}">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            @unless($notification->read_at)
                                                <span class="h-2 w-2 rounded-full bg-indigo-600"></span>
                                            @endunless
                                            <p class="text-sm font-medium text-gray-900">{{ $notification->data['title'] ?? __('Notification') }}</p>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-600">{{ $notification->data['message'] ?? '' }}</p>
                                        <p class="mt-1 text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</p>
                                    </div>
                                    @unless($notification->read_at)
                                        <form action="{{ route('admin.notifications.markRead', $notification->id) }}" method="POST" class="ml-4">
                                            @csrf
                                            <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-900">{{ __('Mark Read') }}</button>
                                        </form>
                                    @endunless
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $notifications->links() }}
                        </div>
                    @else
                        <p class="text-center text-sm text-gray-500">{{ __('No notifications.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
