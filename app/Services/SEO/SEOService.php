<?php

namespace App\Services\SEO;

use App\Models\Post;
use App\Services\AI\AIService;
use Illuminate\Support\Str;

class SEOService
{
    public function __construct(
        protected AIService $aiService
    ) {}
    public function analyzePost(Post $post): array
    {
        $titleScore = $this->scoreTitle($post);
        $descriptionScore = $this->scoreDescription($post);
        $headingsScore = $this->scoreHeadings($post);
        $contentScore = $this->scoreContent($post);
        $imagesScore = $this->scoreImages($post);
        $linksScore = $this->scoreLinks($post);

        $overallScore = (int) round(
            $titleScore * 0.20 +
            $descriptionScore * 0.15 +
            $headingsScore * 0.15 +
            $contentScore * 0.25 +
            $imagesScore * 0.10 +
            $linksScore * 0.15
        );

        return [
            'title_score' => $titleScore,
            'description_score' => $descriptionScore,
            'headings_score' => $headingsScore,
            'content_score' => $contentScore,
            'images_score' => $imagesScore,
            'links_score' => $linksScore,
            'overall_score' => $overallScore,
            'recommendations' => $this->generateRecommendations($post, compact('titleScore', 'descriptionScore', 'headingsScore', 'contentScore', 'imagesScore', 'linksScore')),
        ];
    }

    public function generateMetaTitle(Post $post): string
    {
        return Str::limit($post->title, 60, '');
    }

    public function generateMetaDescription(Post $post): string
    {
        $text = $post->excerpt ?? strip_tags($post->content ?? '');

        return Str::limit($text, 160, '');
    }

    public function suggestKeywords(Post $post): array
    {
        $content = strip_tags($post->content ?? '');
        $content = mb_strtolower($content);
        $words = str_word_count($content, 1);

        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'can', 'could', 'may', 'might', 'shall', 'should', 'this', 'that', 'these', 'those', 'i', 'me', 'my', 'we', 'our', 'you', 'your', 'he', 'him', 'his', 'she', 'her', 'it', 'its', 'they', 'them', 'their', 'what', 'which', 'who', 'whom', 'when', 'where', 'why', 'how', 'all', 'each', 'every', 'both', 'few', 'more', 'most', 'some', 'any', 'no', 'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 'just', 'also', 'if', 'then', 'else', 'as', 'up', 'down', 'out', 'off', 'over', 'such', 'into', 'about'];

        $words = array_filter($words, fn($word) => strlen($word) > 2 && !in_array($word, $stopWords));
        $wordCounts = array_count_values($words);
        arsort($wordCounts);

        $keywords = [];
        foreach (array_slice($wordCounts, 0, 10) as $word => $count) {
            $keywords[] = $word;
        }

        return $keywords;
    }

    protected function scoreTitle(Post $post): int
    {
        $length = mb_strlen($post->title ?? '');
        $score = 0;

        if ($length >= 50 && $length <= 60) {
            $score += 70;
        } elseif ($length >= 40 && $length < 50) {
            $score += 50;
        } elseif ($length > 60 && $length <= 70) {
            $score += 40;
        } elseif ($length >= 30 && $length < 40) {
            $score += 30;
        } else {
            $score += 10;
        }

        $keywordPresent = false;
        if ($post->seo && $post->seo->meta_keywords) {
            $keywords = explode(',', $post->seo->meta_keywords);
            $titleLower = mb_strtolower($post->title);
            foreach ($keywords as $keyword) {
                if (str_contains($titleLower, mb_strtolower(trim($keyword)))) {
                    $keywordPresent = true;
                    break;
                }
            }
        }

        if ($keywordPresent) {
            $score += 30;
        }

        return min(100, $score);
    }

    protected function scoreDescription(Post $post): int
    {
        $text = $post->excerpt ?? strip_tags($post->content ?? '');
        $length = mb_strlen($text);

        if ($length >= 150 && $length <= 160) {
            return 100;
        }
        if ($length >= 120 && $length < 150) {
            return 70;
        }
        if ($length > 160 && $length <= 200) {
            return 60;
        }
        if ($length >= 80 && $length < 120) {
            return 40;
        }

        return 20;
    }

    protected function scoreHeadings(Post $post): int
    {
        $content = $post->content ?? '';
        $score = 0;

        preg_match_all('/<h1[^>]*>/i', $content, $h1Matches);
        if (count($h1Matches[0]) === 1) {
            $score += 40;
        } elseif (count($h1Matches[0]) === 0) {
            $score += 10;
        } else {
            $score += 20;
        }

        preg_match_all('/<h2[^>]*>/i', $content, $h2Matches);
        $h2Count = count($h2Matches[0]);
        if ($h2Count >= 2 && $h2Count <= 6) {
            $score += 40;
        } elseif ($h2Count > 0) {
            $score += 20;
        }

        preg_match_all('/<h[3-6][^>]*>/i', $content, $subMatches);
        if (count($subMatches[0]) > 0) {
            $score += 20;
        }

        return min(100, $score);
    }

    protected function scoreContent(Post $post): int
    {
        $wordCount = $post->word_count ?: str_word_count(strip_tags($post->content ?? ''));
        $score = 0;

        if ($wordCount >= 800) {
            $score += 50;
        } elseif ($wordCount >= 500) {
            $score += 30;
        } elseif ($wordCount >= 300) {
            $score += 15;
        } else {
            $score += 5;
        }

        $sentences = preg_split('/[.!?]+/', strip_tags($post->content ?? ''));
        $sentenceCount = count(array_filter($sentences));
        $avgSentenceLength = $sentenceCount > 0 ? $wordCount / $sentenceCount : 0;

        if ($avgSentenceLength >= 10 && $avgSentenceLength <= 25) {
            $score += 30;
        } elseif ($avgSentenceLength > 0) {
            $score += 15;
        }

        if (!empty($post->excerpt)) {
            $score += 20;
        }

        return min(100, $score);
    }

    protected function scoreImages(Post $post): int
    {
        $content = $post->content ?? '';
        preg_match_all('/<img[^>]+>/i', $content, $imgMatches);
        $totalImages = count($imgMatches[0]);

        if ($totalImages === 0) {
            return $post->featured_image ? 30 : 0;
        }

        preg_match_all('/<img[^>]+alt=["\']([^"\']*)["\'][^>]*>/i', $content, $altMatches);
        $imagesWithAlt = count(array_filter($altMatches[1], fn($alt) => !empty(trim($alt))));

        return (int) round(($imagesWithAlt / $totalImages) * 100);
    }

    protected function scoreLinks(Post $post): int
    {
        $content = $post->content ?? '';
        preg_match_all('/<a[^>]+href=["\']([^"\']*)["\'][^>]*>/i', $content, $linkMatches);
        $links = $linkMatches[1] ?? [];

        $internalCount = 0;
        $appUrl = url('/');
        foreach ($links as $link) {
            if (str_starts_with($link, '/') || str_starts_with($link, $appUrl) || str_starts_with($link, url(''))) {
                $internalCount++;
            }
        }

        if ($internalCount >= 3) {
            return 100;
        }
        if ($internalCount >= 2) {
            return 80;
        }
        if ($internalCount >= 1) {
            return 50;
        }

        return 10;
    }

    protected function generateRecommendations(Post $post, array $scores): array
    {
        $recommendations = [];

        if ($scores['titleScore'] < 70) {
            $length = mb_strlen($post->title ?? '');
            if ($length < 50) {
                $recommendations[] = 'Title is too short (' . $length . ' chars). Aim for 50-60 characters.';
            } elseif ($length > 60) {
                $recommendations[] = 'Title is too long (' . $length . ' chars). Aim for 50-60 characters.';
            }
            if (empty($post->seo?->meta_keywords)) {
                $recommendations[] = 'Add meta keywords to improve title keyword presence.';
            }
        }

        if ($scores['descriptionScore'] < 70) {
            $text = $post->excerpt ?? strip_tags($post->content ?? '');
            $descLen = mb_strlen($text);
            if ($descLen < 120) {
                $recommendations[] = 'Meta description is too short (' . $descLen . ' chars). Aim for 150-160 characters.';
            }
            if (empty($post->excerpt)) {
                $recommendations[] = 'Add an excerpt to serve as the meta description.';
            }
        }

        if ($scores['headingsScore'] < 60) {
            $content = $post->content ?? '';
            preg_match_all('/<h1[^>]*>/i', $content, $h1s);
            if (count($h1s[0]) === 0) {
                $recommendations[] = 'Add an H1 heading to your content.';
            } elseif (count($h1s[0]) > 1) {
                $recommendations[] = 'Multiple H1 tags found. Use only one H1 per page.';
            }
            preg_match_all('/<h2[^>]*>/i', $content, $h2s);
            if (count($h2s[0]) < 2) {
                $recommendations[] = 'Add more H2 subheadings (aim for 2-6) to improve content structure.';
            }
        }

        if ($scores['contentScore'] < 60) {
            $wc = $post->word_count ?: str_word_count(strip_tags($post->content ?? ''));
            if ($wc < 800) {
                $recommendations[] = 'Content is too short (' . $wc . ' words). Aim for at least 800 words.';
            }
        }

        if ($scores['imagesScore'] < 60) {
            $recommendations[] = 'Add alt text to all images for better accessibility and SEO.';
        }

        if ($scores['linksScore'] < 60) {
            $recommendations[] = 'Add more internal links (aim for at least 3) to improve site navigation and SEO.';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Good job! Your post has solid SEO fundamentals.';
        }

        return $recommendations;
    }

    public function calculateReadability(string $content): array
    {
        $text = strip_tags($content);
        $words = str_word_count($text);
        $sentences = preg_split('/[.!?]+/', $text);
        $sentences = array_filter($sentences, fn($s) => trim($s) !== '');
        $sentenceCount = count($sentences);

        $avgWordsPerSentence = $sentenceCount > 0 ? $words / $sentenceCount : 0;
        $syllables = $this->countSyllables($text);
        $avgSyllablesPerWord = $words > 0 ? $syllables / $words : 0;

        $fleschScore = 206.835 - (1.015 * $avgWordsPerSentence) - (84.6 * $avgSyllablesPerWord);
        $fleschScore = max(0, min(100, $fleschScore));

        $paragraphs = explode("\n\n", $text);
        $paragraphs = array_filter($paragraphs, fn($p) => trim($p) !== '');
        $avgParagraphLength = count($paragraphs) > 0 ? $words / count($paragraphs) : 0;

        $longWords = count(array_filter(str_word_count($text, 1), fn($w) => strlen($w) > 6));

        return [
            'score' => round($fleschScore, 1),
            'level' => match (true) {
                $fleschScore >= 90 => 'Very Easy',
                $fleschScore >= 80 => 'Easy',
                $fleschScore >= 70 => 'Fairly Easy',
                $fleschScore >= 60 => 'Standard',
                $fleschScore >= 50 => 'Fairly Difficult',
                $fleschScore >= 30 => 'Difficult',
                default => 'Very Difficult',
            },
            'word_count' => $words,
            'sentence_count' => $sentenceCount,
            'avg_words_per_sentence' => round($avgWordsPerSentence, 1),
            'avg_syllables_per_word' => round($avgSyllablesPerWord, 1),
            'paragraph_count' => count($paragraphs),
            'avg_paragraph_length' => round($avgParagraphLength, 1),
            'long_word_count' => $longWords,
            'long_word_percentage' => $words > 0 ? round($longWords / $words * 100, 1) : 0,
            'suggestions' => $this->generateReadabilitySuggestions($fleschScore, $avgWordsPerSentence, $avgParagraphLength),
        ];
    }

    public function aiOptimizeContent(string $content, string $type = 'seo'): string
    {
        $prompts = [
            'seo' => 'Rewrite this content to improve its SEO performance. Keep all key information but improve keyword placement, heading structure, and readability. Return only the improved content:',
            'readability' => 'Rewrite this to be more readable. Use shorter sentences, simpler words, and better paragraph structure. Keep all key information:',
        ];

        $prompt = ($prompts[$type] ?? $prompts['seo']) . "\n\n" . $content;
        return $this->aiService->generateContent($prompt, 'article') ?: $content;
    }

    private function countSyllables(string $text): int
    {
        $words = str_word_count(mb_strtolower($text), 1);
        $count = 0;
        foreach ($words as $word) {
            $vowels = preg_match_all('/[aeiouy]+/', $word);
            if ($vowels === 0) $vowels = 1;
            if (str_ends_with($word, 'e')) $vowels--;
            if (str_ends_with($word, 'le') && strlen($word) > 2) $vowels++;
            $count += max(1, $vowels);
        }
        return $count;
    }

    private function generateReadabilitySuggestions(float $score, float $avgSentenceLen, float $avgParagraphLen): array
    {
        $suggestions = [];

        if ($score < 50) {
            $suggestions[] = 'Content is difficult to read. Use shorter sentences and simpler words.';
        }
        if ($avgSentenceLen > 25) {
            $suggestions[] = 'Average sentence length is ' . round($avgSentenceLen) . ' words. Aim for 15-20 words per sentence.';
        }
        if ($avgParagraphLen > 100) {
            $suggestions[] = 'Paragraphs are too long (avg ' . round($avgParagraphLen) . ' words). Break them into smaller chunks.';
        }
        if ($score >= 70) {
            $suggestions[] = 'Content has good readability.';
        }

        return $suggestions;
    }
}
