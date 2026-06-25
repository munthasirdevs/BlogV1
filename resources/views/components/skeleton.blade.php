@props(['variant' => 'text', 'width' => '100%', 'height' => null, 'rounded' => true])

@php
$heights = ['text' => '1rem', 'title' => '1.5rem', 'avatar' => '2.5rem', 'image' => '12rem', 'card' => '8rem'];
$h = $height ?? ($heights[$variant] ?? '1rem');
$r = $rounded ? 'rounded-lg' : '';
@endphp

<div {{ $attributes->merge(['class' => 'animate-pulse ' . $r]) }} style="background-color: var(--color-surface-elevated); width: {{ $width }}; height: {{ $h }};"></div>
