<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MonetizationService
{
    protected array $adSlots = [
        'sidebar' => [
            'label' => 'Sidebar Advertisement',
            'width' => 300,
            'height' => 600,
            'responsive' => true,
        ],
        'in-content' => [
            'label' => 'In-Content Advertisement',
            'width' => 728,
            'height' => 90,
            'responsive' => true,
        ],
        'header' => [
            'label' => 'Header Banner',
            'width' => 728,
            'height' => 90,
            'responsive' => true,
        ],
        'footer' => [
            'label' => 'Footer Banner',
            'width' => 728,
            'height' => 90,
            'responsive' => true,
        ],
    ];

    public function getAdSlots(string $location): array
    {
        if (isset($this->adSlots[$location])) {
            return [$location => $this->adSlots[$location]];
        }

        return [];
    }

    public function getAdCode(string $slot): string
    {
        if (!isset($this->adSlots[$slot])) {
            return '';
        }

        $config = $this->adSlots[$slot];

        return sprintf(
            '<div class="ad-container ad-%s" style="width: %dpx; height: %dpx;">%s</div>',
            e($slot),
            $config['width'],
            $config['height'],
            '<!-- Ad code for ' . e($slot) . ' slot -->'
        );
    }

    public function trackClick(string $slot): void
    {
        Log::info('Ad click tracked', [
            'slot' => $slot,
            'timestamp' => now(),
        ]);
    }
}
