<?php

namespace App\Service;

use App\Entity\OffreEmploi;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PublicProfileSourcingService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CvMatchingService $cvMatchingService,
    ) {
    }

    /**
     * @param array<int,string> $urls
     * @return array<int, array{
     *     url:string,
     *     title:string,
     *     score:int,
     *     matched_keywords:array<int,string>,
     *     missing_keywords:array<int,string>,
     *     preview:string,
     *     status:string,
     *     advanced_analysis:?array{
     *         score:int,
     *         summary:string,
     *         recommendation:string,
     *         strengths:array<int,string>,
     *         risks:array<int,string>
     *     }
     * }>
     */
    public function sourceForOffer(OffreEmploi $offreEmploi, array $urls): array
    {
        $results = [];

        foreach ($urls as $url) {
            $url = trim($url);
            if ($url === '') {
                continue;
            }

            if (!$this->isAllowedPublicUrl($url)) {
                $results[] = [
                    'url' => $url,
                    'title' => 'URL refusee',
                    'score' => 0,
                    'matched_keywords' => [],
                    'missing_keywords' => [],
                    'preview' => 'Seules les URLs publiques http/https sont autorisees.',
                    'status' => 'blocked',
                    'advanced_analysis' => null,
                ];
                continue;
            }

            try {
                $response = $this->httpClient->request('GET', $url, [
                    'max_redirects' => 5,
                    'timeout' => 15,
                    'headers' => [
                        'User-Agent' => 'HUMADB Public Sourcing Bot/1.0',
                        'Accept-Language' => 'fr,en;q=0.8',
                    ],
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode >= 400) {
                    throw new \RuntimeException('HTTP '.$statusCode);
                }

                $html = $response->getContent();
                $title = $this->extractTitle($html) ?: parse_url($url, \PHP_URL_HOST) ?: 'Profil public';
                $text = $this->extractVisibleText($html);
                $analysis = $this->cvMatchingService->analyzeTextForOffer($offreEmploi, $text);

                $results[] = [
                    'url' => $url,
                    'title' => $title,
                    'score' => $analysis['score'],
                    'matched_keywords' => $analysis['matched_keywords'],
                    'missing_keywords' => $analysis['missing_keywords'],
                    'preview' => mb_substr($text, 0, 350).(mb_strlen($text) > 350 ? '...' : ''),
                    'status' => 'ok',
                    'advanced_analysis' => $analysis['advanced_analysis'] ?? null,
                ];
            } catch (\Throwable $exception) {
                $results[] = [
                    'url' => $url,
                    'title' => 'Erreur de lecture',
                    'score' => 0,
                    'matched_keywords' => [],
                    'missing_keywords' => [],
                    'preview' => 'Impossible de lire cette page publique: '.$exception->getMessage(),
                    'status' => 'error',
                    'advanced_analysis' => null,
                ];
            }
        }

        usort($results, static fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        return $results;
    }

    private function isAllowedPublicUrl(string $url): bool
    {
        if (!filter_var($url, \FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = parse_url($url, \PHP_URL_SCHEME);

        return in_array($scheme, ['http', 'https'], true);
    }

    private function extractTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches) !== 1) {
            return null;
        }

        return trim(html_entity_decode(strip_tags($matches[1]), \ENT_QUOTES | \ENT_HTML5));
    }

    private function extractVisibleText(string $html): string
    {
        $clean = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html) ?? $html;
        $clean = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $clean) ?? $clean;
        $clean = strip_tags($clean);
        $clean = html_entity_decode($clean, \ENT_QUOTES | \ENT_HTML5);
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? $clean;

        return trim($clean);
    }
}
