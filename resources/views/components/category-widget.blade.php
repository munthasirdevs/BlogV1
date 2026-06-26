@props(['categories' => [], 'title' => 'Categories', 'showCounts' => true])

<div class="rounded-xl p-5" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
    <h4 class="text-xs font-semibold uppercase tracking-wider mb-4" style="color: var(--color-text-muted);">{{ __($title) }}</h4>
    <div class="space-y-2">
        @forelse($categories as $cat)
        <a href="{{ route('category.show', $cat->slug) }}"
           class="flex items-center justify-between group rounded-lg px-3 py-2 transition-colors duration-200 hover:bg-gray-50 dark:hover:bg-slate-800">
            <div class="flex items-center gap-2.5">
                @if($cat->color)<span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $cat->color }}"></span>@endif
                @if($cat->icon)<span class="text-sm">{{ $cat->icon }}</span>@endif
                <span class="text-sm font-medium" style="color: var(--color-text-body);">{{ $cat->name }}</span>
            </div>
            @if($showCounts)
            <span class="text-xs px-2 py-0.5 rounded-full" style="background-color: var(--color-surface-elevated); color: var(--color-text-muted);">
                {{ $cat->article_count }}
            </span>
            @endif
        </a>
        @empty
        <p class="text-sm" style="color: var(--color-text-muted);">{{ __('No categories available.') }}</p>
        @endforelse
    </div>
</div>
