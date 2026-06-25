@props(['checked' => false, 'disabled' => false, 'label' => null, 'name' => null])

<label class="inline-flex items-center gap-2 cursor-pointer">
    <input
        type="radio"
        @checked($checked)
        @disabled($disabled)
        name="{{ $name }}"
        {{ $attributes->merge(['class' => 'focus:ring-2 focus:ring-[var(--color-primary-500)] focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed']) }}
        style="border-color: var(--color-border); color: var(--color-primary-600);"
    >
    @if ($label)
        <span class="text-sm" style="color: var(--color-text-body);">{{ $label }}</span>
    @endif
</label>
