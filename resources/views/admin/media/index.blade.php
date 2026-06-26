<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--color-text-heading)">
                {{ __('Media Library') }}
            </h2>
            <a href="{{ route('admin.media.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">
                {{ __('Upload') }}
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

            <div class="flex gap-6">
                <div class="w-64 shrink-0">
                    <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                        <div class="border-b px-4 py-3" style="border-color: var(--color-border)">
                            <h3 class="text-sm font-semibold" style="color: var(--color-text-body)">{{ __('Folders') }}</h3>
                        </div>
                        <div class="p-2">
                            <a href="{{ route('admin.media.index') }}" class="block rounded-md px-3 py-2 text-sm hover:bg-gray-100 {{ !request('folder') ? 'bg-indigo-50' : '' }}" style="{{ !request('folder') ? 'background-color: var(--color-primary-50); color: var(--color-primary-600)' : 'color: var(--color-text-body)' }}">
                                {{ __('All Files') }}
                            </a>
                            @foreach($folders as $folder)
                                <a href="{{ route('admin.media.index', ['folder' => $folder->id]) }}" class="block rounded-md px-3 py-2 text-sm hover:bg-gray-100 {{ request('folder') == $folder->id ? 'bg-indigo-50' : '' }}" style="{{ request('folder') == $folder->id ? 'background-color: var(--color-primary-50); color: var(--color-primary-600)' : 'color: var(--color-text-body)' }}">
                                    {{ $folder->name }}
                                </a>
                                @if($folder->children->count())
                                    @foreach($folder->children as $child)
                                        <a href="{{ route('admin.media.index', ['folder' => $child->id]) }}" class="block rounded-md px-3 py-2 pl-8 text-sm hover:bg-gray-100 {{ request('folder') == $child->id ? 'bg-indigo-50' : '' }}" style="{{ request('folder') == $child->id ? 'background-color: var(--color-primary-50); color: var(--color-primary-600)' : 'color: var(--color-text-body)' }}">
                                            {{ $child->name }}
                                        </a>
                                    @endforeach
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex-1">
                    <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                        <div class="border-b p-4" style="border-color: var(--color-border)">
                            <div class="flex items-center gap-4">
                                <form method="GET" action="{{ route('admin.media.index') }}" class="flex items-center gap-2">
                                    <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Search files...') }}" class="block w-full max-w-xs rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">
                                    <select name="type" class="rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">
                                        <option value="">{{ __('All Types') }}</option>
                                        <option value="image" {{ request('type') === 'image' ? 'selected' : '' }}>{{ __('Images') }}</option>
                                        <option value="video" {{ request('type') === 'video' ? 'selected' : '' }}>{{ __('Videos') }}</option>
                                        <option value="document" {{ request('type') === 'document' ? 'selected' : '' }}>{{ __('Documents') }}</option>
                                        <option value="audio" {{ request('type') === 'audio' ? 'selected' : '' }}>{{ __('Audio') }}</option>
                                    </select>
                                    <button type="submit" class="inline-flex items-center rounded-md px-3 py-2 text-xs font-semibold text-white" style="background-color: var(--color-primary-600)">{{ __('Filter') }}</button>
                                    @if(request()->anyFilled(['search', 'type']))
                                    <a href="{{ route('admin.media.index') }}" class="text-xs" style="color: var(--color-text-muted)">{{ __('Clear') }}</a>
                                    @endif
                                </form>
                                @if($files->count())
                                    <button id="bulk-delete" class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-red-500 disabled:opacity-50" disabled>
                                        {{ __('Delete Selected') }}
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                                @forelse($files as $file)
                                    <div class="group relative overflow-hidden rounded-lg border" style="border-color: var(--color-border); background-color: var(--color-surface-card)" data-id="{{ $file->id }}">
                                        <label class="absolute left-2 top-2 z-10">
                                            <input type="checkbox" class="file-checkbox rounded shadow-sm focus:ring-indigo-500" style="border-color: var(--color-border); color: var(--color-primary-600)" value="{{ $file->id }}">
                                        </label>
                                        <a href="{{ route('admin.media.edit', $file) }}" class="block">
                                            @if(str_starts_with($file->mime_type, 'image/'))
                                                <div class="aspect-square overflow-hidden bg-gray-100">
                                                    <img src="{{ $file->file_url }}" alt="{{ $file->alt_text ?? $file->original_name }}" class="h-full w-full object-cover transition-transform group-hover:scale-105">
                                                </div>
                                            @elseif(str_starts_with($file->mime_type, 'video/'))
                                                <div class="flex aspect-square items-center justify-center bg-gray-100">
                                                    <svg class="h-12 w-12" style="color: var(--color-text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                </div>
                                            @else
                                                <div class="flex aspect-square items-center justify-center bg-gray-100">
                                                    <svg class="h-12 w-12" style="color: var(--color-text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                </div>
                                            @endif
                                        </a>
                                        <div class="border-t p-2" style="border-color: var(--color-border); background-color: var(--color-surface-card)">
                                            <p class="truncate text-xs font-medium" style="color: var(--color-text-heading)" title="{{ $file->original_name }}">{{ $file->original_name }}</p>
                                            <p class="text-xs" style="color: var(--color-text-muted)">{{ number_format($file->file_size / 1024, 1) }} KB</p>
                                            <div class="mt-1 flex gap-2">
                                                <a href="{{ route('admin.media.edit', $file) }}" class="text-xs hover:text-indigo-900" style="color: var(--color-primary-600)">{{ __('Edit') }}</a>
                                                <form action="{{ route('admin.media.destroy', $file) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs hover:text-red-900" style="color: var(--color-error)" onclick="return confirm('{{ __('Are you sure?') }}')">{{ __('Delete') }}</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-span-full py-12 text-center">
                                        <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-text-muted)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <p class="mt-2 text-sm" style="color: var(--color-text-muted)">{{ __('No files found.') }}</p>
                                        <a href="{{ route('admin.media.create') }}" class="mt-2 inline-flex items-center text-sm hover:text-indigo-900" style="color: var(--color-primary-600)">{{ __('Upload your first file') }}</a>
                                    </div>
                                @endforelse
                            </div>

                            <div class="mt-6">
                                {{ $files->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkboxes = document.querySelectorAll('.file-checkbox');
            const bulkDelete = document.getElementById('bulk-delete');

            checkboxes.forEach(cb => {
                cb.addEventListener('change', function () {
                    const checked = document.querySelectorAll('.file-checkbox:checked');
                    bulkDelete.disabled = checked.length === 0;
                });
            });

            if (bulkDelete) {
                bulkDelete.addEventListener('click', function () {
                    const checked = document.querySelectorAll('.file-checkbox:checked');
                    if (checked.length === 0) return;
                    if (!confirm('{{ __("Are you sure you want to delete the selected files?") }}')) return;

                    const ids = Array.from(checked).map(cb => cb.value);
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("admin.media.bulk-delete") }}';
                    form.innerHTML = '@csrf <input type="hidden" name="ids" value="' + JSON.stringify(ids) + '">';
                    document.body.appendChild(form);
                    form.submit();
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
