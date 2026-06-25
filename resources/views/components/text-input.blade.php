@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-lg shadow-sm w-full focus:ring-2 focus:ring-[var(--color-primary-500)] focus:border-[var(--color-primary-500)] disabled:opacity-50 disabled:cursor-not-allowed']) }} style="background-color: var(--color-surface); border: 1px solid var(--color-border); color: var(--color-text-body);">
