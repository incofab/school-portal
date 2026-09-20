<?php

namespace App\Services\AI\Knowledge;

use App\Contracts\AI\KnowledgeRetriever;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\KnowledgeSearchResult;
use App\DTO\AI\KnowledgeSource;
use App\Enums\FaqType;
use App\Models\Faq;
use App\Support\Faq\FaqContent;
use Illuminate\Support\Str;

class DatabaseKnowledgeRetriever implements KnowledgeRetriever
{
    public function __construct(private readonly FaqPassageChunker $chunker) {}

    public function search(
        string $query,
        AssistantActorContext $_context,
    ): KnowledgeSearchResult
    {
        $terms = $this->terms($query);

        if ($terms === []) {
            return new KnowledgeSearchResult;
        }

        $faqs = Faq::query()
            ->active()
            ->whereIn('type', [FaqType::Faq->value, FaqType::KnowledgeBase->value])
            ->where(function ($search) use ($terms): void {
                foreach ($terms as $term) {
                    $search
                        ->orWhere('name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                }
            })
            ->limit((int) config('ai.knowledge.max_candidates', 200))
            ->get(['id', 'name', 'code', 'description', 'type'])
            ->map(fn (Faq $faq) => $this->bestPassage($faq, $terms))
            ->filter()
            ->sortByDesc('score')
            ->take((int) config('ai.knowledge.max_results', 5));

        return new KnowledgeSearchResult(
            $faqs->map(function (array $result): KnowledgeSource {
                /** @var Faq $faq */
                $faq = $result['faq'];
                return new KnowledgeSource(
                    id: "faq:{$faq->getKey()}",
                    slug: Str::slug("faq-{$faq->code}"),
                    title: $faq->name,
                    excerpt: Str::limit($result['passage'], 420),
                    score: round((float) $result['score'], 3),
                    url: $this->sourceUrl($faq),
                );
            })->values()->all(),
        );
    }

    /**
     * Find the best short passage in an FAQ without storing a second copy of it.
     *
     * @param  array<int, string>  $terms
     * @return array{faq: Faq, passage: string, score: float}|null
     */
    private function bestPassage(Faq $faq, array $terms): ?array
    {
        $content = $this->plainText($faq->description);
        $passages = $this->chunker->chunk($content);

        if ($passages === []) {
            return null;
        }

        $best = collect($passages)
            ->map(fn (string $passage) => [
                'passage' => $passage,
                'score' => $this->score($faq->name, $passage, $terms),
            ])
            ->sortByDesc('score')
            ->first();

        return $best && $best['score'] > 0
            ? ['faq' => $faq, ...$best]
            : null;
    }

    private function plainText(?string $html): string
    {
        $cleanHtml = FaqContent::cleanHtml($html);
        $withBreaks = preg_replace('/<br\s*\/?\s*>/i', "\n", $cleanHtml) ?? $cleanHtml;
        $withBreaks = preg_replace(
            '/<\/(p|li|h[1-6]|tr|div)>/i',
            "$0\n\n",
            $withBreaks,
        ) ?? $withBreaks;
        $text = html_entity_decode(strip_tags($withBreaks), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n\s*\n+/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function sourceUrl(Faq $faq): ?string
    {
        $path = $faq->type === FaqType::KnowledgeBase ? 'knowledge-base' : 'faqs';
        $url = url("{$path}#{$faq->getKey()}");
        $parts = parse_url($url);

        if (! is_array($parts) || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true)) {
            return null;
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    /**
     * @return array<int, string>
     */
    private function terms(string $query): array
    {
        preg_match_all('/[\p{L}\p{N}]{2,}/u', Str::lower($query), $matches);

        $stopWords = [
            'a', 'an', 'and', 'are', 'can', 'do', 'does', 'for', 'how', 'i',
            'in', 'is', 'it', 'my', 'of', 'on', 'or', 'the', 'to', 'what',
            'where', 'why', 'you',
        ];

        $terms = array_values(array_unique(array_filter(
            $matches[0] ?? [],
            fn (string $term) => ! in_array($term, $stopWords, true),
        )));

        return array_slice($terms, 0, 40);
    }

    private function score(string $title, string $passage, array $terms): float
    {
        $content = Str::lower($passage);
        $title = Str::lower($title);
        $score = 0.0;

        foreach ($terms as $term) {
            $score += substr_count($content, $term);
            $score += substr_count($title, $term) * 4;
        }

        return $score;
    }
}
