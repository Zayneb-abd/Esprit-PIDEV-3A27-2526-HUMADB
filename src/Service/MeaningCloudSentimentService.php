<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class MeaningCloudSentimentService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $apiKey,
        private readonly ?string $apiUrl = 'https://api.meaningcloud.com/sentiment-2.1',
        private readonly int $timeoutSeconds = 20,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->normalize($this->apiKey) !== null && $this->normalize($this->apiUrl) !== null;
    }

    /**
     * @return array{
     *     success: bool,
     *     label: string,
     *     description: string,
     *     score_tag: string,
     *     subjectivity: string,
     *     irony: string,
     *     confidence: int,
     *     raw_response?: array<string,mixed>,
     *     error?: string
     * }
     */
    public function analyze(string $text, string $lang = 'fr'): array
    {
        $text = trim($text);

        if ($text === '') {
            return $this->failure('Le texte à analyser est vide.');
        }

        if (!$this->isConfigured()) {
            return $this->failure('MeaningCloud n\'est pas configurée.');
        }

        try {
            $response = $this->httpClient->request('POST', (string) $this->normalize($this->apiUrl), [
                'timeout' => $this->timeoutSeconds,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'key' => (string) $this->normalize($this->apiKey),
                    'txt' => $text,
                    'lang' => $this->normalizeLanguage($lang),
                    'txtf' => 'plain',
                    'of' => 'json',
                ],
            ]);

            $data = $response->toArray(false);

            $statusCode = (string) ($data['status']['code'] ?? '');
            if ($statusCode !== '0') {
                $message = (string) ($data['status']['msg'] ?? 'Réponse MeaningCloud invalide.');

                return $this->failure($message);
            }

            $scoreTag = strtoupper((string) ($data['score_tag'] ?? 'NONE'));
            $subjectivity = strtoupper((string) ($data['subjectivity'] ?? 'UNKNOWN'));
            $irony = strtoupper((string) ($data['irony'] ?? 'UNKNOWN'));

            return [
                'success' => true,
                'score_tag' => $scoreTag,
                'label' => $this->mapScoreTagToLabel($scoreTag),
                'description' => $this->mapScoreTagToDescription($scoreTag),
                'subjectivity' => $subjectivity,
                'irony' => $irony,
                'confidence' => $this->normalizeConfidence($data['confidence'] ?? null),
                'raw_response' => $data,
            ];
        } catch (\Throwable $e) {
            return $this->failure($e->getMessage());
        }
    }

    private function failure(string $message): array
    {
        return [
            'success' => false,
            'label' => 'Non analysé',
            'description' => 'Aucune analyse disponible.',
            'score_tag' => 'NONE',
            'subjectivity' => 'UNKNOWN',
            'irony' => 'UNKNOWN',
            'confidence' => 0,
            'error' => $message,
        ];
    }

    private function mapScoreTagToLabel(string $scoreTag): string
    {
        return match ($scoreTag) {
            'P+' => 'Très positif',
            'P' => 'Positif',
            'NEU' => 'Neutre',
            'N' => 'Négatif',
            'N+' => 'Très négatif',
            default => 'Sans tonalité',
        };
    }

    private function mapScoreTagToDescription(string $scoreTag): string
    {
        return match ($scoreTag) {
            'P+' => 'Le message exprime une émotion très positive.',
            'P' => 'Le message exprime une tonalité positive.',
            'NEU' => 'Le message est globalement neutre.',
            'N' => 'Le message exprime une tonalité négative.',
            'N+' => 'Le message exprime une émotion très négative.',
            default => 'Le sentiment du message n\'a pas pu être déterminé.',
        };
    }

    private function normalizeConfidence(mixed $confidence): int
    {
        if (!is_numeric($confidence)) {
            return 0;
        }

        return max(0, min(100, (int) round((float) $confidence)));
    }

    private function normalizeLanguage(string $lang): string
    {
        $lang = strtolower(trim($lang));

        return $lang !== '' ? $lang : 'fr';
    }

    private function normalize(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
