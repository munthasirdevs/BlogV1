<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight" style="color: var(--color-text-heading)">
            {{ __('Upload Media') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow-sm sm:rounded-lg" style="background-color: var(--color-surface-card)">
                <div class="p-6" style="color: var(--color-text-heading)">
                    @if(session('success'))
                        <div class="mb-4 rounded-md p-4 text-sm" style="background-color: var(--color-success-bg); color: var(--color-success)">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ route('admin.media.store') }}" method="POST" enctype="multipart/form-data" id="upload-form">
                        @csrf

                        <div class="mb-4">
                            <label for="folder_id" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Folder') }}</label>
                            <select name="folder_id" id="folder_id" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)"
                                <option value="">{{ __('Root') }}</option>
                                @foreach($folders as $folder)
                                    <option value="{{ $folder->id }}" {{ old('folder_id') == $folder->id ? 'selected' : '' }}>{{ $folder->name }}</option>
                                @endforeach
                            </select>
                            @error('folder_id') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('File') }}</label>
                            <div id="dropzone" class="mt-1 flex flex-col items-center justify-center rounded-lg border-2 border-dashed px-6 py-12 transition-colors hover:border-indigo-400 hover:bg-indigo-50 cursor-pointer" style="border-color: var(--color-border); background-color: var(--color-surface-elevated)">
                                <svg class="mb-4 h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-text-muted)">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <p class="mb-2 text-sm" style="color: var(--color-text-muted)">
                                    <span class="font-semibold" style="color: var(--color-primary-600)">{{ __('Click to upload') }}</span>
                                    {{ __('or drag and drop') }}
                                </p>
                                <p class="text-xs" style="color: var(--color-text-muted)">
                                    {{ __('JPG, PNG, WebP, GIF, SVG, MP4, PDF, DOCX up to 200MB') }}
                                </p>
                                <input type="file" name="file" id="file" class="hidden" accept=".jpg,.jpeg,.png,.webp,.gif,.svg,.mp4,.pdf,.docx">
                            </div>
                            @error('file') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div id="file-preview" class="mb-4 hidden">
                            <div class="flex items-center gap-4 rounded-lg border p-4" style="border-color: var(--color-border); background-color: var(--color-surface-elevated)">
                                <div id="preview-thumb" class="h-16 w-16 shrink-0 overflow-hidden rounded bg-gray-200">
                                    <img src="" alt="" class="hidden h-full w-full object-cover">
                                    <div class="flex h-full items-center justify-center" style="color: var(--color-text-muted)">
                                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <p id="file-name" class="text-sm font-medium" style="color: var(--color-text-heading)"></p>
                                    <p id="file-size" class="text-sm" style="color: var(--color-text-muted)"></p>
                                </div>
                                <div class="w-32">
                                    <div class="h-2 overflow-hidden rounded-full bg-gray-200">
                                        <div id="progress-bar" class="h-full rounded-full bg-indigo-600 transition-all" style="width: 0%"></div>
                                    </div>
                                    <p id="progress-text" class="mt-1 text-xs" style="color: var(--color-text-muted)">0%</p>
                                </div>
                            </div>
                        </div>

                        <div id="uploaded-files" class="mb-4 hidden">
                            <h4 class="mb-2 text-sm font-medium" style="color: var(--color-text-body)">{{ __('Uploaded Files') }}</h4>
                            <div id="uploaded-list" class="space-y-2"></div>
                        </div>

                        <div class="flex items-center gap-4">
                            <button type="submit" id="submit-btn" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500 disabled:opacity-50" disabled>
                                {{ __('Upload') }}
                            </button>
                            <a href="{{ route('admin.media.index') }}" class="inline-flex items-center rounded-md bg-gray-200 px-4 py-2 text-xs font-semibold uppercase tracking-widest hover:bg-gray-300" style="color: var(--color-text-body)">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dropzone = document.getElementById('dropzone');
            const fileInput = document.getElementById('file');
            const submitBtn = document.getElementById('submit-btn');
            const preview = document.getElementById('file-preview');
            const previewThumb = document.getElementById('preview-thumb');
            const previewImg = previewThumb.querySelector('img');
            const previewIcon = previewThumb.querySelector('div');
            const fileName = document.getElementById('file-name');
            const fileSize = document.getElementById('file-size');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');

            dropzone.addEventListener('click', function () {
                fileInput.click();
            });

            dropzone.addEventListener('dragover', function (e) {
                e.preventDefault();
                dropzone.classList.add('border-indigo-400', 'bg-indigo-50');
            });

            dropzone.addEventListener('dragleave', function () {
                dropzone.classList.remove('border-indigo-400', 'bg-indigo-50');
            });

            dropzone.addEventListener('drop', function (e) {
                e.preventDefault();
                dropzone.classList.remove('border-indigo-400', 'bg-indigo-50');
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    handleFileSelect(e.dataTransfer.files[0]);
                }
            });

            fileInput.addEventListener('change', function () {
                if (this.files.length) {
                    handleFileSelect(this.files[0]);
                }
            });

            function handleFileSelect(file) {
                preview.classList.remove('hidden');
                submitBtn.disabled = false;
                fileName.textContent = file.name;
                fileSize.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';

                if (file.type.startsWith('image/')) {
                    previewImg.classList.remove('hidden');
                    previewIcon.classList.add('hidden');
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        previewImg.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewImg.classList.add('hidden');
                    previewIcon.classList.remove('hidden');
                }
            }

            document.getElementById('upload-form').addEventListener('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                const xhr = new XMLHttpRequest();

                xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        progressBar.style.width = percent + '%';
                        progressText.textContent = percent + '%';
                    }
                });

                xhr.addEventListener('load', function () {
                    if (xhr.status === 200 || xhr.status === 201 || xhr.status === 302) {
                        progressBar.style.width = '100%';
                        progressText.textContent = '{{ __("Complete!") }}';
                        submitBtn.disabled = true;
                        fileInput.value = '';
                        setTimeout(function () {
                            window.location.href = '{{ route("admin.media.index") }}';
                        }, 1000);
                    } else {
                        progressText.textContent = '{{ __("Upload failed") }}';
                    }
                });

                xhr.addEventListener('error', function () {
                    progressText.textContent = '{{ __("Upload failed") }}';
                });

                xhr.open('POST', this.action);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send(formData);
            });
        });
    </script>
    @endpush
</x-app-layout>
