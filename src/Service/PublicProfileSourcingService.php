<?php

namespace App\Service;

use App\Entity\OffreEmploi;

class PublicProfileSourcingService
{
    public function __construct(
        private readonly CvMatchingService $cvMatchingService,
        private readonly RecruitmentProfileScrapingBot $scrapingBot,
    ) {
    }

    /**
     * @param array<int,string> $urls
     * @return array<int, array{
     *     url:string,
     *     title:string,
     *     name:string,
     *     photo:?string,
     *     contact:array{email:?string,phone:?string,website:?string},
     *     description:string,
     *     score:int,
     *     matched_keywords:array<int,string>,
     *     missing_keywords:array<int,string>,
     *     preview:string,
     *     status:string,
     *     source:string,
     *     source_label:string,
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

            try {
                $profile = $this->scrapingBot->scrapeProfile($url, 'manual_url', 'URL manuelle');
                $analysis = $this->cvMatchingService->analyzeTextForOffer(
                    $offreEmploi,
                    trim($profile['title'].' '.$profile['description'].' '.$profile['preview'])
                );

                $results[] = [
                    ...$profile,
                    'score' => $analysis['score'],
                    'matched_keywords' => $analysis['matched_keywords'],
                    'missing_keywords' => $analysis['missing_keywords'],
                    'status' => 'ok',
                    'advanced_analysis' => $analysis['advanced_analysis'] ?? null,
                ];
            } catch (\Throwable $exception) {
                $results[] = [
                    'url' => $url,
                    'title' => 'Erreur de lecture',
                    'name' => 'Profil indisponible',
                    'photo' => null,
                    'contact' => ['email' => null, 'phone' => null, 'website' => $url],
                    'description' => 'Impossible de charger les informations publiques de ce profil.',
                    'score' => 0,
                    'matched_keywords' => [],
                    'missing_keywords' => [],
                    'preview' => 'Le scraping Goutte n a pas pu lire cette page: '.$exception->getMessage(),
                    'status' => 'error',
                    'source' => 'manual_url',
                    'source_label' => 'URL manuelle',
                    'advanced_analysis' => null,
                ];
            }
        }

        usort($results, static fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        return $results;
    }

    /**
     * @return array<int, array{
     *     url:string,
     *     title:string,
     *     name:string,
     *     photo:?string,
     *     contact:array{email:?string,phone:?string,website:?string},
     *     description:string,
     *     score:int,
     *     matched_keywords:array<int,string>,
     *     missing_keywords:array<int,string>,
     *     preview:string,
     *     status:string,
     *     source:string,
     *     source_label:string,
     *     advanced_analysis:?array{
     *         score:int,
     *         summary:string,
     *         recommendation:string,
     *         strengths:array<int,string>,
     *         risks:array<int,string>
     *     }
     * }>
     */
    public function searchForOffer(OffreEmploi $offreEmploi, int $limit = 8): array
    {
        $discoveredProfiles = $this->scrapingBot->discoverProfilesForOffer($offreEmploi, $limit);
        $results = [];

        foreach ($discoveredProfiles as $candidate) {
            try {
                $profile = $this->scrapingBot->scrapeProfile($candidate['url'], $candidate['source'], $candidate['source_label']);
                $analysis = $this->cvMatchingService->analyzeTextForOffer(
                    $offreEmploi,
                    trim($profile['title'].' '.$profile['description'].' '.$profile['preview'])
                );

                $results[] = [
                    ...$profile,
                    'score' => $analysis['score'],
                    'matched_keywords' => $analysis['matched_keywords'],
                    'missing_keywords' => $analysis['missing_keywords'],
                    'status' => 'ok',
                    'advanced_analysis' => $analysis['advanced_analysis'] ?? null,
                ];
            } catch (\Throwable) {
                $analysis = $this->cvMatchingService->analyzeTextForOffer(
                    $offreEmploi,
                    trim($candidate['title'].' '.$candidate['snippet'])
                );

                $results[] = [
                    'url' => $candidate['url'],
                    'title' => $candidate['title'],
                    'name' => $this->normalizeProfileName($candidate['title']),
                    'photo' => null,
                    'contact' => [
                        'email' => null,
                        'phone' => null,
                        'website' => $candidate['url'],
                    ],
                    'description' => $candidate['snippet'] !== '' ? $candidate['snippet'] : 'Profil suggere par le bot de scraping Goutte.',
                    'score' => $analysis['score'],
                    'matched_keywords' => $analysis['matched_keywords'],
                    'missing_keywords' => $analysis['missing_keywords'],
                    'preview' => $candidate['snippet'] !== '' ? $candidate['snippet'] : $candidate['title'],
                    'status' => 'scraping_fallback',
                    'source' => $candidate['source'],
                    'source_label' => $candidate['source_label'],
                    'advanced_analysis' => $analysis['advanced_analysis'] ?? null,
                ];
            }
        }

        usort($results, static fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        return $results;
    }

    private function normalizeProfileName(string $title): string
    {
        $name = preg_split('/[|\-–•·]+/u', $title, 2)[0] ?? $title;
        $name = trim($name);

        return $name !== '' ? $name : 'Profil public';
    }
}
