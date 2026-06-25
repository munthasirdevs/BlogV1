@props(['disabled' => false])

<button @disabled($disabled) {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 rounded-lg font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-[var(--color-error)] focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition ease-in-out duration-150']) }} style="background-color: var(--color-error);" onmouseover="this.style.backgroundColor='#dc2626'" onmouseout="this.style.backgroundColor='var(--color-error)'">
    {{ $slot }}
</button>
