<?php

namespace App\Services\AI;

class PromptTemplateService
{
    protected array $templates = [
        'blog_article' => [
            'system' => 'You are a professional blog writer. Write engaging, well-structured content.',
            'structure' => "Write a blog article about: {topic}\n\nFormat: Use HTML headings and paragraphs. Include an introduction, 3-5 sections, and a conclusion.\nWord count target: {word_count} words.\nStyle: {style}",
        ],
        'seo_title' => [
            'system' => 'You are an SEO specialist. Generate compelling, keyword-rich titles.',
            'structure' => "Generate an SEO-optimized title (max 60 characters) for this content:\n\n{content}\n\nReturn ONLY the title, no explanation.",
        ],
        'seo_description' => [
            'system' => 'You are an SEO specialist. Generate click-worthy meta descriptions.',
            'structure' => "Generate an SEO meta description (max 160 characters) for:\n\nTitle: {title}\nContent: {content}\n\nReturn ONLY the description.",
        ],
        'keywords' => [
            'system' => 'You are a keyword research specialist.',
            'structure' => "Extract the top 10 most relevant SEO keywords from this content. Return as comma-separated list:\n\n{content}",
        ],
        'summary' => [
            'system' => 'You are a content summarizer. Create concise summaries.',
            'structure' => "Summarize this content in 2-3 paragraphs:\n\n{content}",
        ],
    ];

    public function build(string $template, array $params = []): string
    {
        $tpl = $this->templates[$template] ?? null;
        if (!$tpl) return $params['prompt'] ?? '';

        $structure = $tpl['structure'];
        foreach ($params as $key => $value) {
            $structure = str_replace('{' . $key . '}', (string) $value, $structure);
        }

        return $tpl['system'] . "\n\n" . $structure;
    }

    public function getTemplate(string $name): ?array
    {
        return $this->templates[$name] ?? null;
    }

    public function getTemplates(): array
    {
        return array_keys($this->templates);
    }
}
