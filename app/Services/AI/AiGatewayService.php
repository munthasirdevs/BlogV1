<?php

namespace App\Services\AI;

use App\Services\AiCacheService;
use App\Services\Billing\UsageMeterService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AiGatewayService
{
    protected array $modelProfiles = [
        'fast' => ['model' => 'mixtral-8x7b-instruct-v0.1', 'max_tokens' => 1024, 'temperature' => 0.3],
        'balanced' => ['model' => 'mixtral-8x7b-instruct-v0.1', 'max_tokens' => 2048, 'temperature' => 0.5],
        'creative' => ['model' => 'mixtral-8x7b-instruct-v0.1', 'max_tokens' => 4096, 'temperature' => 0.8],
    ];

    public function __construct(
        protected AIService $aiService,
        protected AiCacheService $cacheService,
        protected UsageMeterService $usageMeter
    ) {}

    public function generate(string $prompt, string $type = 'article', string $profile = 'balanced'): string
    {
        if (!$this->checkRateLimit()) {
            throw new \RuntimeException('AI rate limit reached. Please try again later.');
        }

        $prompt = $this->truncatePrompt($prompt);
        $tenantId = tenant_id();
        $profile = $this->modelProfiles[$profile] ?? $this->modelProfiles['balanced'];

        $cachedResult = $this->cacheService->remember($prompt, $type, function () use ($prompt, $type, $profile, $tenantId) {
            $start = microtime(true);

            $result = $this->aiService->generateContent($prompt, $type);

            $duration = (microtime(true) - $start) * 1000;
            $tokens = ceil(strlen($prompt . $result) / 4);

            if ($tenantId) {
                $cost = $tokens * 0.000002;
                $this->usageMeter->recordAiUsage($tenantId, $tokens, $cost);
            }

            Log::info('AI Gateway request', [
                'type' => $type,
                'profile' => $profile['model'],
                'duration_ms' => round($duration, 1),
                'tokens' => $tokens,
                'tenant_id' => $tenantId,
            ]);

            return $result;
        });

        return $cachedResult;
    }

    private function checkRateLimit(): bool
    {
        $key = 'ai_rate_limit:' . date('Y-m-d-H');
        $count = (int) Cache::get($key, 0);
        if ($count >= config('ai.rate_limit', 50)) {
            Log::warning('AI rate limit reached');
            return false;
        }
        Cache::put($key, $count + 1, 3600);
        return true;
    }

    private function truncatePrompt(string $prompt): string
    {
        $max = config('ai.max_prompt_chars', 500);
        if (mb_strlen($prompt) <= $max) return $prompt;
        $truncated = mb_substr($prompt, 0, $max);
        $lastSpace = mb_strrpos($truncated, ' ');
        return $lastSpace > 0 ? mb_substr($truncated, 0, $lastSpace) . '...' : $truncated . '...';
    }

    public function getProfile(string $name): ?array
    {
        return $this->modelProfiles[$name] ?? null;
    }

    public function getProfiles(): array
    {
        return array_keys($this->modelProfiles);
    }

    public function estimateCost(string $prompt, string $profile = 'balanced'): float
    {
        $tokens = ceil(strlen($prompt) / 4);
        return $tokens * 0.000002;
    }
}
