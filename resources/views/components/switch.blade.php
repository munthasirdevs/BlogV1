@props(['checked' => false, 'disabled' => false, 'label' => null])

<label class="inline-flex items-center gap-3 cursor-pointer">
    <button
        type="button"
        role="switch"
        aria-checked="{{ $checked ? 'true' : 'false' }}"
        @disabled($disabled)
        x-data="{ on: @js($checked) }"
        @click="on = !on"
        :aria-checked="on"
        {{ $attributes->merge(['class' => 'relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-500)] focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed']) }}
        :style="on ? 'background-color: var(--color-primary-600);' : 'background-color: var(--color-border);'"
    >
        <span
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
            :class="{ 'translate-x-5': on, 'translate-x-0': !on }"
        ></span>
    </button>
    @if ($label)
        <span class="text-sm" style="color: var(--color-text-body);">{{ $label }}</span>
    @endif
</label>
