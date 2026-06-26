@props(['tags' => [], 'title' => 'Tags', 'max' => null])

@php
    $displayTags = $max ? $tags->take($max) : $tags;
    $maxUsage = $tags->max('usage_count') ?: 1;
@endphp

<div class="rounded-xl p-5" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
    <h4 class="text-xs font-semibold uppercase tracking-wider mb-4" style="color: var(--color-text-muted);">{{ __($title) }}</h4>
    <div class="flex flex-wrap gap-2">
        @forelse($displayTags as $tag)
        <a href="{{ route('tag.show', $tag->slug) }}"
           class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg font-medium transition-all duration-200 hover:bg-gray-100 dark:hover:bg-slate-800"
           style="background-color: var(--color-surface-elevated); color: var(--color-text-body); border: 1px solid var(--color-border); font-size: {{ max(0.7, 0.7 + ($tag->usage_count / $maxUsage) * 0.5) }}rem;">
            @if($tag->color)<span class="w-1.5 h-1.5 rounded-full shrink-0" style="background-color: {{ $tag->color }}"></span>@endif
            {{ $tag->name }}
        </a>
        @empty
        <p class="text-sm" style="color: var(--color-text-muted);">{{ __('No tags yet.') }}</p>
        @endforelse
    </div>
</div>
