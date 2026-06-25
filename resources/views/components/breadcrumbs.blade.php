@props(['items' => []])

<nav {{ $attributes->merge(['class' => 'flex items-center gap-2 text-sm']) }} aria-label="Breadcrumb">
    @foreach ($items as $label => $url)
        @if ($loop->last)
            <span class="font-medium" style="color: var(--color-text-heading);" aria-current="page">{{ $label }}</span>
        @else
            <a href="{{ $url }}" class="hover:underline" style="color: var(--color-text-muted);">{{ $label }}</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-text-muted);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        @endif
    @endforeach
</nav>
