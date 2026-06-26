<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\RateLimiter;

class CommentService
{
    protected array $spamKeywords = [
        'buy now', 'click here', 'free money', 'earn money fast',
        'viagra', 'casino', 'lottery', 'prize',
    ];

    public function __construct(
        protected AIService $aiService
    ) {}

    public function submit(array $data, ?string $ip): Comment
    {
        $post = Post::published()->findOrFail($data['post_id']);

        $key = 'comment:' . ($ip ?? 'unknown');
        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw new \RuntimeException('Too many comments. Please wait before posting again.');
        }
        RateLimiter::hit($key, 60);

        $aiScore = null;
        try {
            $aiScore = $this->aiModerate($data['body']);
        } catch (\Exception $e) {
        }

        $isSpam = $aiScore !== null && $aiScore < 0.3;
        if (!$isSpam) {
            $isSpam = $this->checkBasicSpam($data['body']);
        }

        $comment = Comment::create([
            'post_id' => $post->id,
            'parent_id' => $data['parent_id'] ?? null,
            'user_id' => auth()->id(),
            'guest_name' => $data['guest_name'] ?? null,
            'guest_email' => $data['guest_email'] ?? null,
            'body' => $data['body'],
            'status' => $isSpam ? 'spam' : 'pending',
            'ip_address' => $ip,
            'ai_moderation_score' => $aiScore,
        ]);

        RateLimiter::clear($key);

        return $comment;
    }

    public function aiModerate(string $content): float
    {
        $prompt = "Analyze this comment for spam, toxicity, and quality. Return ONLY a JSON object with these fields:\n- score (0-1, where 1 is high quality, 0 is spam)\n- is_toxic (true/false)\n- reason (brief explanation)\n\nComment: {$content}";

        $response = $this->aiService->generateContent($prompt, 'audit');
        $parsed = json_decode($response, true);

        if ($parsed && isset($parsed['score'])) {
            return (float) $parsed['score'];
        }

        return 0.5;
    }

    protected function checkBasicSpam(string $body): bool
    {
        $bodyLower = mb_strtolower($body);

        if (preg_match('/https?:\/\/[^\s]+/i', $body, $matches)) {
            $urlCount = count($matches);
            if ($urlCount > 2) return true;
        }

        foreach ($this->spamKeywords as $keyword) {
            if (str_contains($bodyLower, $keyword)) return true;
        }

        return false;
    }

    public function search(string $term, ?string $status = null, int $perPage = 20)
    {
        $query = Comment::with('post', 'user');

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('body', 'like', "%{$term}%")
                  ->orWhere('guest_name', 'like', "%{$term}%")
                  ->orWhere('guest_email', 'like', "%{$term}%");
            });
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
    }
}
