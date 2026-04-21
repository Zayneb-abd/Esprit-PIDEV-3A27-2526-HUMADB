<?php

namespace App\Service;

use App\Entity\Feedback;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FeedbackAutoResponseGenerator
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $openAiApiKey = null,
        private readonly string $openAiModel = 'gpt-4o-mini',
    ) {
    }

    public function generate(Feedback $feedback): string
    {
        $content = trim((string) $feedback->getContenu());
        $category = trim((string) $feedback->getCategory());

        if ($content === '') {
            return "Merci pour votre retour. Pouvez-vous préciser davantage votre demande (exemples, étapes, contexte) afin que nous puissions vous aider efficacement ?";
        }

        if (!$this->openAiApiKey) {
            return $this->fallbackResponse($category, $content);
        }

        try {
            $system = "Tu es un assistant RH. Rédige une réponse courte, professionnelle, empathique, en français. "
                . "Ne promets rien d'impossible, ne demande pas d'informations personnelles sensibles. "
                . "Structure: 1) remerciement, 2) reformulation, 3) prochaines étapes/clarifications, 4) délai indicatif.";

            $user = "Catégorie: {$category}\n\nMessage:\n{$content}\n\nRédige une réponse.";

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
                    'temperature' => 0.4,
                    'max_tokens' => 250,
                ],
            ]);

            $data = $response->toArray(false);
            $text = (string) ($data['choices'][0]['message']['content'] ?? '');
            $text = trim($text);

            if ($text !== '') {
                return $text;
            }
        } catch (\Throwable) {
            // fall back below
        }

        return $this->fallbackResponse($category, $content);
    }

    private function fallbackResponse(string $category, string $content): string
    {
        $categoryLower = mb_strtolower($category);

        if (str_contains($categoryLower, 'soutien')) {
            return "Merci pour votre retour. Nous avons bien pris en compte votre demande de soutien technique.\n\n"
                . "Pouvez-vous nous préciser l'outil concerné, le message d'erreur (si disponible) et les étapes pour reproduire le problème ? "
                . "Dès réception, nous lancerons l'analyse et reviendrons vers vous avec une solution ou un contournement.\n\n"
                . "Délai indicatif : 24–48h ouvrées.";
        }

        if (str_contains($categoryLower, 'plainte')) {
            return "Merci de nous avoir signalé ce point. Nous comprenons que la situation puisse être frustrante et nous la prenons au sérieux.\n\n"
                . "Nous allons analyser votre retour et, si nécessaire, planifier un échange pour clarifier le contexte. "
                . "Vous serez informé(e) des suites données.\n\n"
                . "Délai indicatif : 48–72h ouvrées.";
        }

        if (str_contains($categoryLower, 'suggestion')) {
            return "Merci pour votre suggestion. Nous l'avons bien enregistrée.\n\n"
                . "Nous allons l'évaluer (impact, faisabilité, priorité) et la partager avec l'équipe concernée. "
                . "Si vous avez un exemple d'usage ou le bénéfice attendu, cela nous aidera à mieux la prioriser.\n\n"
                . "Délai indicatif : retour initial sous 72h ouvrées.";
        }

        return "Merci pour votre message. Nous avons bien pris en compte votre retour.\n\n"
            . "Pour nous aider à traiter au mieux votre demande, pouvez-vous ajouter un peu de contexte (exemples concrets, date/lieu si pertinent, impact) ? "
            . "Nous reviendrons vers vous dès que possible avec les prochaines étapes.\n\n"
            . "Délai indicatif : 48–72h ouvrées.";
    }
}

