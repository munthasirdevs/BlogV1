<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
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
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="flex gap-6">
                <div class="w-64 shrink-0">
                    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-4 py-3">
                            <h3 class="text-sm font-semibold text-gray-700">{{ __('Folders') }}</h3>
                        </div>
                        <div class="p-2">
                            <a href="{{ route('admin.media.index') }}" class="block rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ !request('folder') ? 'bg-indigo-50 text-indigo-700' : '' }}">
                                {{ __('All Files') }}
                            </a>
                            @foreach($folders as $folder)
                                <a href="{{ route('admin.media.index', ['folder' => $folder->id]) }}" class="block rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request('folder') == $folder->id ? 'bg-indigo-50 text-indigo-700' : '' }}">
                                    {{ $folder->name }}
                                </a>
                                @if($folder->children->count())
                                    @foreach($folder->children as $child)
                                        <a href="{{ route('admin.media.index', ['folder' => $child->id]) }}" class="block rounded-md px-3 py-2 pl-8 text-sm text-gray-600 hover:bg-gray-100 {{ request('folder') == $child->id ? 'bg-indigo-50 text-indigo-700' : '' }}">
                                            {{ $child->name }}
                                        </a>
                                    @endforeach
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex-1">
                    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div class="border-b border-gray-200 p-4">
                            <div class="flex items-center gap-4">
                                <input type="text" id="search" placeholder="{{ __('Search files...') }}" class="block w-full max-w-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <select id="type-filter" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">{{ __('All Types') }}</option>
                                    <option value="image">{{ __('Images') }}</option>
                                    <option value="video">{{ __('Videos') }}</option>
                                    <option value="document">{{ __('Documents') }}</option>
                                    <option value="audio">{{ __('Audio') }}</option>
                                </select>
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
                                    <div class="group relative overflow-hidden rounded-lg border border-gray-200 bg-white" data-id="{{ $file->id }}">
                                        <label class="absolute left-2 top-2 z-10">
                                            <input type="checkbox" class="file-checkbox rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" value="{{ $file->id }}">
                                        </label>
                                        <a href="{{ route('admin.media.edit', $file) }}" class="block">
                                            @if(str_starts_with($file->mime_type, 'image/'))
                                                <div class="aspect-square overflow-hidden bg-gray-100">
                                                    <img src="{{ $file->file_url }}" alt="{{ $file->alt_text ?? $file->original_name }}" class="h-full w-full object-cover transition-transform group-hover:scale-105">
                                                </div>
                                            @elseif(str_starts_with($file->mime_type, 'video/'))
                                                <div class="flex aspect-square items-center justify-center bg-gray-100">
                                                    <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                </div>
                                            @else
                                                <div class="flex aspect-square items-center justify-center bg-gray-100">
                                                    <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                </div>
                                            @endif
                                        </a>
                                        <div class="border-t border-gray-200 bg-white p-2">
                                            <p class="truncate text-xs font-medium text-gray-900" title="{{ $file->original_name }}">{{ $file->original_name }}</p>
                                            <p class="text-xs text-gray-500">{{ number_format($file->file_size / 1024, 1) }} KB</p>
                                            <div class="mt-1 flex gap-2">
                                                <a href="{{ route('admin.media.edit', $file) }}" class="text-xs text-indigo-600 hover:text-indigo-900">{{ __('Edit') }}</a>
                                                <form action="{{ route('admin.media.destroy', $file) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs text-red-600 hover:text-red-900" onclick="return confirm('{{ __('Are you sure?') }}')">{{ __('Delete') }}</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-span-full py-12 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <p class="mt-2 text-sm text-gray-500">{{ __('No files found.') }}</p>
                                        <a href="{{ route('admin.media.create') }}" class="mt-2 inline-flex items-center text-sm text-indigo-600 hover:text-indigo-900">{{ __('Upload your first file') }}</a>
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
                    form.action = '{{ route("admin.media.index") }}';
                    form.innerHTML = '@csrf @method("DELETE") <input type="hidden" name="ids" value="' + JSON.stringify(ids) + '">';
                    document.body.appendChild(form);
                    form.submit();
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
