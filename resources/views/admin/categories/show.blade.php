<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--color-text-heading)">
                {{ $category->name }}
            </h2>
            <a href="{{ route('admin.categories.edit', $category) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">
                {{ __('Edit Category') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                    <div class="p-6">
                        @if($category->image)
                        <img src="{{ $category->image }}" alt="{{ $category->name }}" class="w-full h-48 object-cover rounded-lg mb-6">
                        @endif
                        <h1 class="text-2xl font-bold" style="color: var(--color-text-heading)">{{ $category->name }}</h1>
                        @if($category->short_description)
                        <p class="mt-2" style="color: var(--color-text-body)">{{ $category->short_description }}</p>
                        @endif
                        @if($category->full_description)
                        <div class="mt-4 prose max-w-none" style="color: var(--color-text-body)">{{ $category->full_description }}</div>
                        @endif
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                        <div class="p-4 space-y-2 text-sm">
                            <div class="flex justify-between"><span style="color: var(--color-text-muted)">Status</span><span>{{ ucfirst($category->status) }}</span></div>
                            <div class="flex justify-between"><span style="color: var(--color-text-muted)">Articles</span><span>{{ $category->article_count }}</span></div>
                            <div class="flex justify-between"><span style="color: var(--color-text-muted)">Featured</span><span>{{ $category->featured ? 'Yes' : 'No' }}</span></div>
                            <div class="flex justify-between"><span style="color: var(--color-text-muted)">Slug</span><span>{{ $category->slug }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
