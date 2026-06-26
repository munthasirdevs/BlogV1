<?php

namespace App\Services\SEO;

use App\Models\Post;
use App\Services\AI\AIService;
use Illuminate\Support\Collection;

class KeywordService
{
    protected array $stopWords = [
        'the','a','an','and','or','but','in','on','at','to','for','of','with','by',
        'from','is','are','was','were','be','been','being','have','has','had','do',
        'does','did','will','would','can','could','may','might','shall','should',
        'this','that','these','those','i','me','my','we','our','you','your','he',
        'him','his','she','her','it','its','they','them','their','what','which',
        'who','whom','when','where','why','how','all','each','every','both','few',
        'more','most','some','any','no','not','only','own','same','so','than','too',
        'very','just','also','if','then','else','as','up','down','out','off','over',
        'such','into','about','than','then','because','while','during','before',
        'after','above','below','between','through','without','within','along',
    ];

    public function __construct(
        protected AIService $aiService
    ) {}

    public function extractFromContent(string $content, int $limit = 10): array
    {
        $text = strip_tags($content);
        $text = mb_strtolower($text);
        $words = str_word_count($text, 1);
        $words = array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $this->stopWords));
        $counts = array_count_values($words);
        arsort($counts);

        $keywords = [];
        foreach (array_slice($counts, 0, $limit) as $word => $count) {
            $keywords[] = ['keyword' => $word, 'count' => $count, 'density' => round($count / max(count($words), 1) * 100, 2)];
        }

        return $keywords;
    }

    public function extractWithAi(string $content, int $limit = 10): array
    {
        $prompt = "Extract the top {$limit} most relevant SEO keywords from this content. Return as a comma-separated list of individual words or short phrases (2-3 words). Focus on topic relevance and search intent.\n\nContent:\n" . mb_substr($content, 0, 3000);

        $response = $this->aiService->generateContent($prompt, 'keywords');

        if (empty($response)) return [];

        return array_map('trim', explode(',', $response));
    }

    public function suggestFocusKeyword(string $title, string $content): string
    {
        $prompt = "Suggest a single focus keyword for this blog post. The keyword should be the primary topic that best represents the content. Return only the keyword, nothing else.\n\nTitle: {$title}\nContent: " . mb_substr(strip_tags($content), 0, 500);

        $response = $this->aiService->generateContent($prompt, 'meta_description');
        return trim($response ?: $title);
    }

    public function calculateDensity(string $content, string $keyword): float
    {
        $text = mb_strtolower(strip_tags($content));
        $wordCount = str_word_count($text);
        $keywordCount = mb_substr_count($text, mb_strtolower($keyword));
        return $wordCount > 0 ? round(($keywordCount / $wordCount) * 100, 2) : 0;
    }
}
