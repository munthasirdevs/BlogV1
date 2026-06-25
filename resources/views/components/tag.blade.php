@props(['removable' => false, 'color' => null])

<span
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium']) }}
    @if ($color) style="background-color: {{ $color }}15; color: {{ $color }};" @else style="background-color: var(--color-primary-100); color: var(--color-primary-700);" @endif
>
    {{ $slot }}
    @if ($removable)
        <button type="button" class="hover:opacity-75 focus:outline-none" aria-label="Remove">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
        </button>
    @endif
</span>
