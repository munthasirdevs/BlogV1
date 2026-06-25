@props(['disabled' => false])

<button @disabled($disabled) {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 rounded-lg font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-500)] focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition ease-in-out duration-150']) }} style="color: var(--color-text-body); background-color: transparent;" onmouseover="this.style.backgroundColor='var(--color-surface-elevated)'" onmouseout="this.style.backgroundColor='transparent'">
    {{ $slot }}
</button>
