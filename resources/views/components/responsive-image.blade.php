@props([
    'src', 'alt' => '', 'sizes' => '100vw', 'loading' => 'lazy',
    'class' => '', 'placeholder' => null, 'width' => null, 'height' => null,
])

@php
    $style = '';
    if ($width && $height) {
        $style = "aspect-ratio: {$width}/{$height};";
    }
    $variantBase = (string) Str::of($src)->beforeLast('.');
@endphp

<div class="relative overflow-hidden bg-gray-100 {{ $class }}"@if($style) style="{{ $style }}"@endif>
    @if($placeholder)
    <div class="absolute inset-0 bg-cover bg-center blur-xl scale-110"
         style="background-image: url('{{ $placeholder }}')">
    </div>
    @endif
    <picture class="relative block w-full h-full">
        <source
            srcset="
                {{ $variantBase }}_thumb.webp 150w,
                {{ $variantBase }}_small.webp 300w,
                {{ $variantBase }}_medium.webp 768w
            "
            sizes="{{ $sizes }}"
            type="image/webp"
        >
        <img
            src="{{ $variantBase }}_medium.webp"
            alt="{{ $alt }}"
            width="{{ $width ?? 768 }}"
            height="{{ $height ?? 432 }}"
            loading="{{ $loading }}"
            decoding="async"
            class="relative w-full h-full object-cover"
            onerror="this.onerror=null; this.src='{{ $src }}'"
        >
    </picture>
</div>
