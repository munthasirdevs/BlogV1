<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ResponsiveImage extends Component
{
    public function __construct(
        public string $src,
        public string $alt = '',
        public string $sizes = '100vw',
        public string $loading = 'lazy',
        public string $class = '',
        public ?string $placeholder = null,
        public ?int $width = null,
        public ?int $height = null,
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.responsive-image');
    }
}
