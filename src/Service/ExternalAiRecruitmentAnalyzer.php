<?php

namespace App\Service;

use App\Entity\OffreEmploi;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExternalAiRecruitmentAnalyzer
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $apiUrl,
        private readonly ?string $apiKey,
        private readonly ?string $model,
        private readonly int $timeoutSeconds = 25,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->normalize($this->apiUrl) !== null
            && $this->normalize($this->apiKey) !== null
            && $this->normalize($this->model) !== null;
    }

    /**
     * @param array{score?:int,matched_keywords?:array<int,string>,missing_keywords?:array<int,string>,quiz_score?:float|null} $context
     * @return array{
     *     score:int,
     *     summary:string,
     *     recommendation:string,
     *     strengths:array<int,string>,
     *     risks:array<int,string>,
     *     raw_response?:string
     * }
     */
    public function analyzeOfferMatch(OffreEmploi $offreEmploi, string $profileText, array $context = []): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('External AI API is not configured.');
        }

        $payload = [
            'model' => $this->normalize($this->model),
            'temperature' => 0.2,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Tu es un assistant RH. Analyse l adequation entre une offre et un profil. Reponds uniquement en JSON valide avec les cles: score, summary, recommendation, strengths, risks.',
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildPrompt($offreEmploi, $profileText, $context),
                ],
            ],
        ];

        $response = $this->httpClient->request('POST', (string) $this->normalize($this->apiUrl), [
            'timeout' => $this->timeoutSeconds,
            'headers' => [
                'Authorization' => 'Bearer '.$this->normalize($this->apiKey),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => $payload,
        ]);

        $data = $response->toArray(false);
        $content = (string) ($data['choices'][0]['message']['content'] ?? '');
        $decoded = $this->decodeJsonPayload($content);

        return [
            'score' => $this->normalizeScore($decoded['score'] ?? null),
            'summary' => $this->normalizeSentence($decoded['summary'] ?? 'Analyse disponible via API externe.'),
            'recommendation' => $this->normalizeRecommendation($decoded['recommendation'] ?? null),
            'strengths' => $this->normalizeList($decoded['strengths'] ?? []),
            'risks' => $this->normalizeList($decoded['risks'] ?? []),
            'raw_response' => $content,
        ];
    }

    /**
     * @param array{score?:int,matched_keywords?:array<int,string>,missing_keywords?:array<int,string>,quiz_score?:float|null} $context
     */
    private function buildPrompt(OffreEmploi $offreEmploi, string $profileText, array $context): string
    {
        $offerText = trim(implode("\n", array_filter([
            'Titre: '.($offreEmploi->getTitre() ?? 'N/A'),
            'Departement: '.($offreEmploi->getDepartement() ?? 'N/A'),
            'Contrat: '.($offreEmploi->getTypeContrat() ?? 'N/A'),
            'Description: '.($offreEmploi->getDescription() ?? 'N/A'),
        ])));

        $heuristicContext = trim(implode("\n", array_filter([
            isset($context['score']) ? 'Score heuristique local: '.$context['score'].'/100' : null,
            isset($context['quiz_score']) && $context['quiz_score'] !== null ? 'Score quiz: '.round((float) $context['quiz_score'], 1).'%' : null,
            !empty($context['matched_keywords']) ? 'Mots-cles identifies: '.implode(', ', $context['matched_keywords']) : null,
            !empty($context['missing_keywords']) ? 'Mots-cles manquants: '.implode(', ', $context['missing_keywords']) : null,
        ])));

        return <<<PROMPT
Analyse ce profil pour un recruteur.

OFFRE
{$offerText}

CONTEXTE LOCAL
{$heuristicContext}

PROFIL
{$profileText}

Contraintes:
- Donne un score global de 0 a 100.
- "recommendation" doit etre un de ces libelles: FORTE, MOYENNE, FAIBLE.
- "strengths" et "risks" doivent contenir 1 a 3 elements courts.
- "summary" doit tenir en 2 phrases maximum.
- Reponds uniquement avec un objet JSON valide.
PROMPT;
    }

    /**
     * @return array<string,mixed>
     */
    private function decodeJsonPayload(string $content): array
    {
        $trimmed = trim($content);
        $trimmed = preg_replace('/^```(?:json)?\s*/i', '', $trimmed) ?? $trimmed;
        $trimmed = preg_replace('/\s*```$/', '', $trimmed) ?? $trimmed;

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $trimmed, $matches) === 1) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new \RuntimeException('Invalid AI JSON response.');
    }

    /**
     * @param mixed $value
     * @return array<int,string>
     */
    private function normalizeList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            if (!is_scalar($item)) {
                continue;
            }

            $normalized = $this->normalizeSentence((string) $item);
            if ($normalized === '') {
                continue;
            }

            $items[] = $normalized;
        }

        return array_slice(array_values(array_unique($items)), 0, 3);
    }

    private function normalizeRecommendation(mixed $value): string
    {
        $normalized = strtoupper(trim((string) $value));

        return in_array($normalized, ['FORTE', 'MOYENNE', 'FAIBLE'], true) ? $normalized : 'MOYENNE';
    }

    private function normalizeSentence(mixed $value): string
    {
        $sentence = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');

        return mb_substr($sentence, 0, 280);
    }

    private function normalizeScore(mixed $value): int
    {
        if (!is_numeric($value)) {
            return 0;
        }

        return max(0, min(100, (int) round((float) $value)));
    }

    private function normalize(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
