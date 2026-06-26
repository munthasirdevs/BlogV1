@props(['title', 'value', 'icon' => null, 'trend' => null, 'trendUp' => true])

<div {{ $attributes->merge(['class' => 'rounded-lg shadow-sm p-6']) }} style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium" style="color: var(--color-text-muted);">{{ $title }}</p>
            <p class="text-2xl font-bold mt-1" style="color: var(--color-text-heading);">{{ $value }}</p>
            @if ($trend)
                <p class="text-sm mt-1" style="color: {{ $trendUp ? 'var(--color-success)' : 'var(--color-error)' }};">
                    {{ $trendUp ? '↑' : '↓' }} {{ $trend }}
                </p>
            @endif
        </div>
        @if ($icon)
            <div class="p-3 rounded-lg" style="background-color: var(--color-primary-100); color: var(--color-primary-600);">
                {{ $icon }}
            </div>
        @endif
    </div>
</div>
