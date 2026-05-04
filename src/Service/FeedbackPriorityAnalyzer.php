<?php

namespace App\Service;

use App\Entity\Feedback;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FeedbackPriorityAnalyzer
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $openAiApiKey = null,
        private readonly string $openAiModel = 'gpt-4o-mini',
    ) {
    }

    public function analyze(Feedback $feedback): string
    {
        $content = trim((string) $feedback->getContenu());
        $category = trim((string) $feedback->getCategory());

        if ($content === '') {
            return 'normal';
        }

        // Si la clé API est absente, on utilise l'analyseur par mots-clés (fallback)
        if (!$this->openAiApiKey) {
            return $this->fallbackAnalysis($category, $content);
        }

        try {
            $system = "Tu es un système de triage RH. Ton seul but est d'attribuer une priorité à un feedback employé. "
                . "Tu dois répondre UNIQUEMENT par l'un de ces 4 mots exacts en minuscules : 'bas', 'normal', 'haute', 'urgente'. "
                . "Ne mets pas de ponctuation ni d'explication. "
                . "Critères :\n"
                . "- urgente : danger imminent, harcèlement grave, arrêt de travail forcé.\n"
                . "- haute : problème technique bloquant, conflit sérieux, plainte formelle.\n"
                . "- normal : question générale, petite remarque.\n"
                . "- bas : suggestion mineure, compliment, idée.";

            $user = "Catégorie: {$category}\n\nMessage:\n{$content}";

            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openAiApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->openAiModel,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 10,
                ],
            ]);

            $data = $response->toArray(false);
            $text = mb_strtolower(trim((string) ($data['choices'][0]['message']['content'] ?? '')));

            // Sécurité : on s'assure que la réponse est valide
            $validPriorities = ['bas', 'normal', 'haute', 'urgente'];
            if (in_array($text, $validPriorities, true)) {
                return $text;
            }
        } catch (\Throwable) {
            // En cas d'erreur de réseau ou d'API, on utilise le fallback
        }

        return $this->fallbackAnalysis($category, $content);
    }

    private function fallbackAnalysis(string $category, string $content): string
    {
        $contentLower = mb_strtolower($content);
        $categoryLower = mb_strtolower($category);

        // Mots-clés pour 'urgente' (racines des mots)
        $urgentKeywords = ['harcel', 'danger', 'accident', 'urgent', 'menace', 'grave', 'violence', 'suicide'];
        foreach ($urgentKeywords as $word) {
            if (str_contains($contentLower, $word)) {
                return 'urgente';
            }
        }

        // Mots-clés pour 'haute'
        $highKeywords = ['bug', 'panne', 'bloqué', 'bloque', 'problème', 'probleme', 'impossible', 'critique'];
        if (str_contains($categoryLower, 'plainte')) {
            return 'haute';
        }
        foreach ($highKeywords as $word) {
            if (str_contains($contentLower, $word)) {
                return 'haute';
            }
        }

        // Mots-clés pour 'bas'
        $lowKeywords = ['suggestion', 'idée', 'idee', 'amélioration', 'amelioration', 'merci', 'bien'];
        if (str_contains($categoryLower, 'suggestion')) {
            return 'bas';
        }
        foreach ($lowKeywords as $word) {
            if (str_contains($contentLower, $word)) {
                return 'bas';
            }
        }

        return 'normal';
    }
}
