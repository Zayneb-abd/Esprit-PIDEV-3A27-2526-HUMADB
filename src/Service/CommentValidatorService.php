<?php

namespace App\Service;

class CommentValidatorService
{
    /**
     * Liste locale de secours si BanBuilder n'est pas installé.
     * On garde une liste courte pour éviter de bloquer le flux commentaire.
     * @var array<string>
     */
    private array $badWords = [
        'sale',
        'idiot',
        'stupide',
        'merde',
        'con',
        'connard',
        'pute',
        'fuck',
        'shit',
    ];

    /**
     * @return array<string, mixed>
     */
    public function validateComment(string $content): array
    {
        $found = $this->detectBadWords($content);

        return [
            'is_valid' => empty($found),
            'is_censored' => !empty($found),
            'bad_words_found' => $found,
            'censored_content' => $this->censorContent($content),
            'message' => empty($found) ? '' : 'Commentaire contenant des mots inappropriés détectés',
        ];
    }

    /**
     * @return array<string>
     */
    private function detectBadWords(string $content): array
    {
        $found = [];
        $normalized = mb_strtolower($content);

        foreach ($this->badWords as $word) {
            if ($word !== '' && preg_match('/\b' . preg_quote($word, '/') . '\b/u', $normalized)) {
                $found[] = $word;
            }
        }

        return array_values(array_unique($found));
    }

    private function censorContent(string $content): string
    {
        $censored = $content;

        foreach ($this->badWords as $word) {
            $replacement = preg_replace(
                '/\b' . preg_quote($word, '/') . '\b/iu',
                str_repeat('*', max(3, mb_strlen($word))),
                $censored
            );
            if ($replacement !== null) {
                $censored = $replacement;
            }
        }

        return $censored;
    }

    /**
     * @param array<string> $words
     */
    public function addCustomBadWords(array $words): void
    {
        foreach ($words as $word) {
            $word = trim((string) $word);
            if ($word !== '' && !in_array($word, $this->badWords, true)) {
                $this->badWords[] = $word;
            }
        }
    }

    public function isBadWord(string $word): bool
    {
        return in_array(mb_strtolower(trim($word)), $this->badWords, true);
    }

    /**
     * @return array<string>
     */
    public function getBadWordsList(): array
    {
        return $this->badWords;
    }
}
