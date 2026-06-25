@props(['disabled' => false, 'loading' => false])

<button @disabled($disabled || $loading) {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2 rounded-lg font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-500)] focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition ease-in-out duration-150']) }} style="background-color: var(--color-primary-600);" onmouseover="this.style.backgroundColor='var(--color-primary-700)'" onmouseout="this.style.backgroundColor='var(--color-primary-600)'" onmousedown="this.style.backgroundColor='var(--color-primary-800)'" onmouseup="this.style.backgroundColor='var(--color-primary-700)'">
    @if ($loading)
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @endif
    {{ $slot }}
</button>
