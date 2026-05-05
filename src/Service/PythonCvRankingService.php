<?php

namespace App\Service;

use App\Entity\OffreEmploi;
use Symfony\Component\Process\Process;

class PythonCvRankingService
{
    public function __construct(
        private readonly string $projectDir,
        private readonly string $pythonBinary = 'python3',
        private readonly int $timeoutSeconds = 20,
    ) {
    }

    /**
     * @param array<int, string> $candidateTexts
     * @return array<int, array{
     *     index:int,
     *     score:int,
     *     matched_terms:array<int,string>,
     *     missing_terms:array<int,string>,
     *     similarity:float
     * }>
     */
    public function rankOfferCandidates(OffreEmploi $offreEmploi, array $candidateTexts): array
    {
        $payload = [
            'title' => $offreEmploi->getTitre() ?? '',
            'department' => $offreEmploi->getDepartement() ?? '',
            'contract' => $offreEmploi->getTypeContrat() ?? '',
            'description' => $offreEmploi->getDescription() ?? '',
            'candidates' => array_map(static fn (string $text): array => ['text' => $text], $candidateTexts),
        ];

        try {
            $process = new Process([
                $this->pythonBinary,
                $this->projectDir.'/python/cv_ranker.py',
            ]);
            $process->setInput(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $process->setTimeout($this->timeoutSeconds);
            $process->run();

            $decoded = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($decoded) || !isset($decoded['rankings']) || !is_array($decoded['rankings'])) {
                throw new \RuntimeException('Invalid Python ranking response.');
            }

            return $this->normalizeRankings($decoded['rankings'], count($candidateTexts));
        } catch (\Throwable) {
            return $this->fallbackRank($offreEmploi, $candidateTexts);
        }
    }

    /**
     * @param array<int, mixed> $rankings
     * @return array<int, array{
     *     index:int,
     *     score:int,
     *     matched_terms:array<int,string>,
     *     missing_terms:array<int,string>,
     *     similarity:float
     * }>
     */
    private function normalizeRankings(array $rankings, int $expectedCount): array
    {
        $normalized = [];
        for ($i = 0; $i < $expectedCount; $i++) {
            $normalized[$i] = [
                'index' => $i,
                'score' => 0,
                'matched_terms' => [],
                'missing_terms' => [],
                'similarity' => 0.0,
            ];
        }

        foreach ($rankings as $ranking) {
            if (!is_array($ranking) || !isset($ranking['index'])) {
                continue;
            }

            $index = (int) $ranking['index'];
            if (!array_key_exists($index, $normalized)) {
                continue;
            }

            $normalized[$index] = [
                'index' => $index,
                'score' => max(0, min(100, (int) ($ranking['score'] ?? 0))),
                'matched_terms' => $this->stringList($ranking['matched_terms'] ?? []),
                'missing_terms' => $this->stringList($ranking['missing_terms'] ?? []),
                'similarity' => isset($ranking['similarity']) && is_numeric($ranking['similarity']) ? (float) $ranking['similarity'] : 0.0,
            ];
        }

        return array_values($normalized);
    }

    /**
     * @param array<int, mixed> $value
     * @return array<int, string>
     */
    private function stringList(array $value): array
    {
        $items = [];
        foreach ($value as $item) {
            if (!is_scalar($item)) {
                continue;
            }

            $text = trim((string) $item);
            if ($text === '') {
                continue;
            }

            $items[] = $text;
        }

        return array_values(array_unique($items));
    }

    /**
     * @param array<int, string> $candidateTexts
     * @return array<int, array{
     *     index:int,
     *     score:int,
     *     matched_terms:array<int,string>,
     *     missing_terms:array<int,string>,
     *     similarity:float
     * }>
     */
    private function fallbackRank(OffreEmploi $offreEmploi, array $candidateTexts): array
    {
        $offerText = implode(' ', array_filter([
            $offreEmploi->getTitre(),
            $offreEmploi->getDepartement(),
            $offreEmploi->getTypeContrat(),
            $offreEmploi->getDescription(),
        ]));

        $offerTokens = $this->tokenize($offerText);
        $offerSet = array_fill_keys($offerTokens, true);

        $rankings = [];
        foreach ($candidateTexts as $index => $candidateText) {
            $candidateTokens = $this->tokenize($candidateText);
            $matched = array_values(array_intersect(array_keys($offerSet), $candidateTokens));
            $coverage = $offerTokens === [] ? 0.0 : count($matched) / count(array_unique($offerTokens));
            $score = (int) round($coverage * 100);

            $rankings[] = [
                'index' => $index,
                'score' => $score,
                'matched_terms' => array_slice($matched, 0, 8),
                'missing_terms' => array_slice(array_values(array_diff(array_unique($offerTokens), $matched)), 0, 8),
                'similarity' => $coverage,
            ];
        }

        usort($rankings, static fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        return $rankings;
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(string $text): array
    {
        $normalized = mb_strtolower($text);
        $normalized = preg_replace('/[^a-z0-9\-\+\# ]+/u', ' ', $normalized) ?? '';
        $parts = preg_split('/\s+/', $normalized, -1, \PREG_SPLIT_NO_EMPTY) ?: [];

        $stopWords = [
            'avec', 'pour', 'dans', 'vous', 'nous', 'leur', 'elles', 'ils', 'une', 'des', 'les', 'sur', 'par',
            'est', 'sont', 'etre', 'avoir', 'plus', 'moins', 'this', 'that', 'from', 'your', 'will', 'need',
            'and', 'the', 'aux', 'ses', 'nos', 'vos', 'notre', 'votre', 'entre', 'dont', 'afin', 'tout', 'tous',
            'toute', 'toutes', 'comme', 'chez', 'sans', 'mais', 'donc', 'car', 'offre', 'emploi', 'poste',
            'profil', 'candidat', 'candidature', 'experience', 'ans', 'dans', 'une', 'dun', 'du', 'de', 'la',
            'le', 'un', 'en', 'au', 'ou', 'et', 'a', 'to', 'of', 'in', 'on', 'for',
        ];

        $tokens = [];
        foreach ($parts as $part) {
            if (mb_strlen($part) < 3 || in_array($part, $stopWords, true)) {
                continue;
            }

            $tokens[] = $part;
        }

        return array_values(array_unique($tokens));
    }
}
