@props(['src' => null, 'alt' => '', 'size' => 'md', 'fallback' => null, 'status' => null])

@php
$sizes = ['xs' => 'w-6 h-6 text-xs', 'sm' => 'w-8 h-8 text-sm', 'md' => 'w-10 h-10 text-base', 'lg' => 'w-12 h-12 text-lg', 'xl' => 'w-16 h-16 text-xl'];
$sizeClass = $sizes[$size] ?? $sizes['md'];
$initials = $fallback ?? (function($name) {
    $parts = explode(' ', trim($name));
    return strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
})($alt);
@endphp

<div class="relative inline-flex shrink-0">
    @if ($src)
        <img src="{{ $src }}" alt="{{ $alt }}" class="{{ $sizeClass }} rounded-full object-cover" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
        <span class="{{ $sizeClass }} rounded-full items-center justify-center font-medium" style="background-color: var(--color-primary-100); color: var(--color-primary-700); display: none;">{{ $initials }}</span>
    @else
        <span class="{{ $sizeClass }} rounded-full inline-flex items-center justify-center font-medium" style="background-color: var(--color-primary-100); color: var(--color-primary-700);">{{ $initials }}</span>
    @endif
    @if ($status)
        <span class="absolute bottom-0 right-0 block w-2.5 h-2.5 rounded-full ring-2 ring-white" style="background-color: {{ $status === 'online' ? 'var(--color-success)' : ($status === 'away' ? 'var(--color-warning)' : 'var(--color-text-muted)') }};"></span>
    @endif
</div>
