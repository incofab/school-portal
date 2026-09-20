<?php

namespace App\Services\AI\Knowledge;

use Illuminate\Support\Str;

class FaqPassageChunker
{
    /**
     * Split FAQ text at paragraph boundaries before falling back
     * to bounded overlapping windows for unusually long paragraphs.
     *
     * @return array<int, string>
     */
    public function chunk(string $content): array
    {
        $content = trim(preg_replace('/[ \t]+/', ' ', $content) ?? $content);
        $content = trim(preg_replace('/\R{3,}/', "\n\n", $content) ?? $content);

        if ($content === '') {
            return [];
        }

        $maxLength = max(200, (int) config('ai.knowledge.chunk_size', 1200));
        $overlap = min(
            max(0, (int) config('ai.knowledge.chunk_overlap', 120)),
            (int) floor($maxLength / 3),
        );
        $chunks = [];
        $current = '';

        foreach (preg_split('/\R{2,}/', $content) ?: [$content] as $paragraph) {
            $paragraph = trim($paragraph);

            if ($paragraph === '') {
                continue;
            }

            if (Str::length($paragraph) > $maxLength) {
                if ($current !== '') {
                    $chunks[] = $current;
                    $current = '';
                }

                foreach ($this->windows($paragraph, $maxLength, $overlap) as $window) {
                    $chunks[] = $window;
                }

                continue;
            }

            $candidate = $current === '' ? $paragraph : "{$current}\n\n{$paragraph}";

            if (Str::length($candidate) <= $maxLength) {
                $current = $candidate;
            } else {
                $chunks[] = $current;
                $current = $paragraph;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return array_values(array_filter(array_map('trim', $chunks)));
    }

    /**
     * @return array<int, string>
     */
    private function windows(string $content, int $maxLength, int $overlap): array
    {
        $windows = [];
        $start = 0;
        $length = Str::length($content);
        $step = max(1, $maxLength - $overlap);

        while ($start < $length) {
            $windows[] = trim(Str::substr($content, $start, $maxLength));
            $start += $step;
        }

        return $windows;
    }
}
