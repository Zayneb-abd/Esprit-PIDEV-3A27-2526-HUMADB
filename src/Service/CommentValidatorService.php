<?php

namespace App\Service;

use Snipe\BanBuilder\CensorWords;

class CommentValidatorService
{
    private CensorWords $censorWords;

    public function __construct()
    {
        $this->censorWords = new CensorWords();
        $this->censorWords->setDictionary('fr'); // Dictionnaire français
    }

    /**
     * Valide un commentaire et détecte les mots interdits
     */
    public function validateComment(string $content): array
    {
        $result = [
            'is_valid' => true,
            'is_censored' => false,
            'bad_words_found' => [],
            'censored_content' => $content,
            'message' => ''
        ];

        // Détecter les mots interdits
        $censoredResult = $this->censorWords->censorString($content);
        
        // censorString retourne un array, on extrait le contenu censuré
        $censoredContent = is_array($censoredResult) ? (isset($censoredResult['clean']) ? $censoredResult['clean'] : $content) : $censoredResult;
        
        if ($censoredContent !== $content) {
            $result['is_valid'] = false;
            $result['is_censored'] = true;
            $result['bad_words_found'] = $this->extractBadWords($content, $censoredContent);
            $result['censored_content'] = $censoredContent;
            $result['message'] = 'Commentaire contenant des mots inappropriés détectés';
        }

        return $result;
    }

    /**
     * Extrait les mots interdits du contenu
     */
    private function extractBadWords(string $content, string $censoredContent): array
    {
        $badWords = [];
        
        // Comparer le contenu original avec le contenu censuré pour trouver les mots
        $originalWords = str_word_count(strtolower($content), 1);
        $censoredWords = str_word_count(strtolower($censoredContent), 1);
        
        $badWords = array_diff($originalWords, $censoredWords);
        
        return array_unique($badWords);
    }

    /**
     * Censure le contenu en remplaçant les mots interdits
     */
    private function censorContent(string $content): string
    {
        $censoredResult = $this->censorWords->censorString($content);
        return is_array($censoredResult) ? (isset($censoredResult['clean']) ? $censoredResult['clean'] : $content) : $censoredResult;
    }

    /**
     * Ajoute des mots personnalisés à la liste noire
     */
    public function addCustomBadWords(array $words): void
    {
        foreach ($words as $word) {
            $this->censorWords->addWord($word);
        }
    }

    /**
     * Vérifie si un mot est dans la liste noire
     */
    public function isBadWord(string $word): bool
    {
        $censoredResult = $this->censorWords->censorString($word);
        $filtered = is_array($censoredResult) ? (isset($censoredResult['clean']) ? $censoredResult['clean'] : $word) : $censoredResult;
        return $filtered !== $word;
    }

    /**
     * Obtient la liste des mots dans la liste noire
     */
    public function getBadWordsList(): array
    {
        return $this->censorWords->getDictionary();
    }
}
