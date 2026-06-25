@props(['current' => 1, 'total' => 1, 'perPage' => 15])

@php
$lastPage = (int) ceil($total / $perPage);
$prev = max(1, $current - 1);
$next = min($lastPage, $current + 1);
@endphp

@if ($lastPage > 1)
    <nav {{ $attributes->merge(['class' => 'flex items-center justify-center gap-1']) }} aria-label="Pagination">
        <a href="{{ $prev !== $current ? '?page=' . $prev : '#' }}"
            class="px-3 py-2 rounded-lg text-sm {{ $prev !== $current ? '' : 'pointer-events-none opacity-50' }}"
            style="color: var(--color-text-body); border: 1px solid var(--color-border); background-color: var(--color-surface);"
            aria-label="Previous page">&lsaquo;</a>

        @for ($i = 1; $i <= $lastPage; $i++)
            @if ($i === $current)
                <span class="px-3 py-2 rounded-lg text-sm font-medium"
                    style="background-color: var(--color-primary-600); color: white;">{{ $i }}</span>
            @elseif ($i === 1 || $i === $lastPage || abs($i - $current) <= 1)
                <a href="?page={{ $i }}"
                    class="px-3 py-2 rounded-lg text-sm"
                    style="color: var(--color-text-body); border: 1px solid var(--color-border); background-color: var(--color-surface);">{{ $i }}</a>
            @elseif ($i === 2 || $i === $lastPage - 1)
                <span class="px-2" style="color: var(--color-text-muted);">&hellip;</span>
            @endif
        @endfor

        <a href="{{ $next !== $current ? '?page=' . $next : '#' }}"
            class="px-3 py-2 rounded-lg text-sm {{ $next !== $current ? '' : 'pointer-events-none opacity-50' }}"
            style="color: var(--color-text-body); border: 1px solid var(--color-border); background-color: var(--color-surface);"
            aria-label="Next page">&rsaquo;</a>
    </nav>
@endif
