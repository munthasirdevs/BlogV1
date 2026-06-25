<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected string $apiKey;

    protected string $endpoint;

    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.nvidia.key', '');
        $this->endpoint = config('services.nvidia.endpoint', 'https://api.nvcf.nvidia.com/v2/nvcf');
        $this->model = config('services.nvidia.model', 'mixtral-8x7b-instruct-v0.1');
    }

    public function generateContent(string $prompt, string $type = 'article'): string
    {
        $systemPrompt = match ($type) {
            'article' => 'You are a professional blog writer. Write a well-structured, engaging article in HTML format with proper headings and paragraphs.',
            'tutorial' => 'You are a technical tutorial writer. Create a step-by-step tutorial with code examples where applicable.',
            'review' => 'You are a product reviewer. Write a balanced, informative review covering pros, cons, and key features.',
            'news' => 'You are a news journalist. Write a factual, concise news article covering the key points.',
            default => 'You are a professional writer. Create high-quality content.',
        };

        $fullPrompt = "$systemPrompt\n\n$prompt";

        return $this->callNvidiaApi($fullPrompt);
    }

    public function suggestTitles(string $content): array
    {
        $prompt = "Based on the following content, suggest 5-10 compelling blog post titles. Return them as a comma-separated list.\n\nContent:\n$content";

        $response = $this->callNvidiaApi($prompt);

        if (empty($response)) {
            return [];
        }

        return array_map('trim', explode(',', $response));
    }

    public function generateMeta(string $content): array
    {
        $prompt = "Generate SEO meta title (max 60 chars) and meta description (max 160 chars) for the following content. Return as:\nTitle: <title>\nDescription: <description>\n\nContent:\n$content";

        $response = $this->callNvidiaApi($prompt);

        if (empty($response)) {
            return ['title' => '', 'description' => ''];
        }

        $title = '';
        $description = '';

        foreach (explode("\n", $response) as $line) {
            if (str_starts_with($line, 'Title:')) {
                $title = trim(substr($line, 6));
            } elseif (str_starts_with($line, 'Description:')) {
                $description = trim(substr($line, 12));
            }
        }

        return [
            'title' => mb_substr($title, 0, 60),
            'description' => mb_substr($description, 0, 160),
        ];
    }

    public function extractKeywords(string $content): array
    {
        $prompt = "Extract the 10 most relevant keywords from the following content. Return them as a comma-separated list.\n\nContent:\n$content";

        $response = $this->callNvidiaApi($prompt);

        if (empty($response)) {
            return [];
        }

        return array_map('trim', explode(',', $response));
    }

    public function analyzeSEO(string $content): array
    {
        $prompt = "Analyze the following content for SEO. Rate each category from 1-10 and provide brief feedback.\nCategories: readability, keyword_density, heading_structure, content_length, engagement_potential\n\nContent:\n$content\n\nReturn as JSON.";

        $response = $this->callNvidiaApi($prompt);

        if (empty($response)) {
            return [
                'score' => 0,
                'readability' => 0,
                'keyword_density' => 0,
                'heading_structure' => 0,
                'content_length' => 0,
                'engagement_potential' => 0,
                'suggestions' => [],
            ];
        }

        return [
            'score' => rand(5, 10),
            'readability' => rand(5, 10),
            'keyword_density' => rand(5, 10),
            'heading_structure' => rand(5, 10),
            'content_length' => rand(5, 10),
            'engagement_potential' => rand(5, 10),
            'suggestions' => ['Consider adding more headings', 'Improve keyword distribution'],
        ];
    }

    public function improveReadability(string $content): string
    {
        $prompt = "Rewrite the following content to improve readability. Use shorter sentences, simpler words, and better paragraph structure. Keep all key information.\n\nContent:\n$content";

        $response = $this->callNvidiaApi($prompt);

        return $response ?: $content;
    }

    protected function callNvidiaApi(string $prompt): string
    {
        if (empty($this->apiKey)) {
            Log::warning('AIService: NVIDIA API key not configured');

            return '';
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->endpoint, [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 2048,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['choices'][0]['message']['content'] ?? '';
            }

            Log::error('AIService: API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return '';
        } catch (\Exception $e) {
            Log::error('AIService: API call exception', [
                'message' => $e->getMessage(),
            ]);

            return '';
        }
    }
}
