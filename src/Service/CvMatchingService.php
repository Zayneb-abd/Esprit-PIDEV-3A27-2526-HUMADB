<?php

namespace App\Service;

use App\Entity\Candidature;
use App\Entity\OffreEmploi;
use App\Entity\Quiz;
use App\Repository\ResultatQuizRepository;

class CvMatchingService
{
    private const STOP_WORDS = [
        'avec', 'pour', 'dans', 'vous', 'nous', 'leur', 'elles', 'ils', 'une', 'des', 'les', 'sur', 'par',
        'est', 'sont', 'etre', 'avoir', 'plus', 'moins', 'this', 'that', 'from', 'your', 'will', 'need',
        'and', 'the', 'aux', 'ses', 'nos', 'vos', 'notre', 'votre', 'entre', 'dont', 'afin', 'tout', 'tous',
        'toute', 'toutes', 'comme', 'chez', 'sans', 'mais', 'donc', 'car', 'offre', 'emploi', 'poste',
        'profil', 'candidat', 'candidature', 'experience', 'ans', 'dans', 'une', 'dun', 'du', 'de', 'la',
        'le', 'un', 'en', 'au', 'ou', 'et', 'a', 'to', 'of', 'in', 'on', 'for',
    ];

    public function __construct(
        private readonly CandidateCvManager $candidateCvManager,
        private readonly PdfCvPreviewService $pdfCvPreviewService,
        private readonly ResultatQuizRepository $resultatQuizRepository,
        private readonly ExternalAiRecruitmentAnalyzer $externalAiRecruitmentAnalyzer,
        private readonly int $advancedAiMaxCandidates = 3,
    ) {
    }

    /**
     * @param iterable<Candidature> $candidatures
     * @return array<int, array{
     *     candidature:Candidature,
     *     score:int,
     *     matched_keywords:array<int,string>,
     *     missing_keywords:array<int,string>,
     *     quiz_score:float|null,
     *     cv_preview:?string,
     *     advanced_analysis:?array{
     *         score:int,
     *         summary:string,
     *         recommendation:string,
     *         strengths:array<int,string>,
     *         risks:array<int,string>
     *     },
     *     analysis_mode:string
     * }>
     */
    public function rankForOffer(OffreEmploi $offreEmploi, iterable $candidatures): array
    {
        $keywords = $this->extractKeywords($offreEmploi);
        $quiz = $this->findQuizForOffer($offreEmploi);
        $ranked = [];

        foreach ($candidatures as $candidature) {
            if (!$candidature instanceof Candidature) {
                continue;
            }

            $cvPreview = $this->pdfCvPreviewService->extractPreview(
                $this->candidateCvManager->getApplicationCvAbsolutePath($candidature->getCv()),
                2200
            );

            $cvKeywords = $this->extractKeywordsFromText($cvPreview ?? '');
            $matched = array_values(array_intersect($keywords, $cvKeywords));
            $missing = array_values(array_diff($keywords, $matched));
            $baseScore = $keywords === [] ? 0 : (int) round((count($matched) / count($keywords)) * 100);

            $quizScore = null;
            if ($quiz instanceof Quiz && $candidature->getUser() !== null) {
                $result = $this->resultatQuizRepository->findOneBy([
                    'user' => $candidature->getUser(),
                    'quiz' => $quiz,
                ]);

                if ($result !== null && $result->getScorePourcentage() !== null) {
                    $quizScore = (float) $result->getScorePourcentage();
                }
            }

            $finalScore = $baseScore;
            if ($quizScore !== null) {
                $finalScore = (int) round(($baseScore * 0.7) + ($quizScore * 0.3));
            }

            $ranked[] = [
                'candidature' => $candidature,
                'score' => max(0, min(100, $finalScore)),
                'matched_keywords' => array_slice($matched, 0, 8),
                'missing_keywords' => array_slice($missing, 0, 8),
                'quiz_score' => $quizScore,
                'cv_preview' => $cvPreview,
                'advanced_analysis' => null,
                'analysis_mode' => 'local',
            ];
        }

        usort($ranked, static function (array $left, array $right): int {
            return $right['score'] <=> $left['score'];
        });

        if ($this->externalAiRecruitmentAnalyzer->isConfigured()) {
            foreach (array_slice(array_keys($ranked), 0, max(0, $this->advancedAiMaxCandidates)) as $index) {
                $profileText = $ranked[$index]['cv_preview'] ?? null;
                if (!is_string($profileText) || trim($profileText) === '') {
                    continue;
                }

                try {
                    $advanced = $this->externalAiRecruitmentAnalyzer->analyzeOfferMatch($offreEmploi, $profileText, [
                        'score' => $ranked[$index]['score'],
                        'matched_keywords' => $ranked[$index]['matched_keywords'],
                        'missing_keywords' => $ranked[$index]['missing_keywords'],
                        'quiz_score' => $ranked[$index]['quiz_score'],
                    ]);

                    $ranked[$index]['advanced_analysis'] = $advanced;
                    $ranked[$index]['analysis_mode'] = 'external_ai';
                    $ranked[$index]['score'] = $advanced['score'];
                } catch (\Throwable) {
                    $ranked[$index]['analysis_mode'] = 'local_fallback';
                }
            }

            usort($ranked, static function (array $left, array $right): int {
                return $right['score'] <=> $left['score'];
            });
        }

        return $ranked;
    }

    /**
     * @return array{
     *     score:int,
     *     matched_keywords:array<int,string>,
     *     missing_keywords:array<int,string>,
     *     advanced_analysis:?array{
     *         score:int,
     *         summary:string,
     *         recommendation:string,
     *         strengths:array<int,string>,
     *         risks:array<int,string>
     *     },
     *     analysis_mode:string
     * }
     */
    public function analyzeTextForOffer(OffreEmploi $offreEmploi, string $text): array
    {
        $keywords = $this->extractKeywords($offreEmploi);
        $profileKeywords = $this->extractKeywordsFromText($text);
        $matched = array_values(array_intersect($keywords, $profileKeywords));
        $missing = array_values(array_diff($keywords, $matched));
        $score = $keywords === [] ? 0 : (int) round((count($matched) / count($keywords)) * 100);

        $advancedAnalysis = $this->buildAdvancedTextAnalysis($offreEmploi, $text, [
            'score' => max(0, min(100, $score)),
            'matched_keywords' => array_slice($matched, 0, 8),
            'missing_keywords' => array_slice($missing, 0, 8),
        ]);

        return [
            'score' => $advancedAnalysis['score'] ?? max(0, min(100, $score)),
            'matched_keywords' => array_slice($matched, 0, 8),
            'missing_keywords' => array_slice($missing, 0, 8),
            'advanced_analysis' => $advancedAnalysis,
            'analysis_mode' => $advancedAnalysis !== null ? 'external_ai' : ($this->externalAiRecruitmentAnalyzer->isConfigured() ? 'local_fallback' : 'local'),
        ];
    }

    /**
     * @return array<int,string>
     */
    private function extractKeywords(OffreEmploi $offreEmploi): array
    {
        $source = implode(' ', array_filter([
            $offreEmploi->getTitre(),
            $offreEmploi->getDepartement(),
            $offreEmploi->getTypeContrat(),
            $offreEmploi->getDescription(),
        ]));

        return $this->extractKeywordsFromText($source);
    }

    /**
     * @return array<int,string>
     */
    private function extractKeywordsFromText(string $text): array
    {
        $normalized = mb_strtolower($text);
        $normalized = preg_replace('/[^a-z0-9\-\+\# ]+/u', ' ', $normalized) ?? '';
        $parts = preg_split('/\s+/', $normalized, -1, \PREG_SPLIT_NO_EMPTY) ?: [];

        $keywords = [];
        foreach ($parts as $part) {
            if (mb_strlen($part) < 3) {
                continue;
            }

            if (in_array($part, self::STOP_WORDS, true)) {
                continue;
            }

            $keywords[$part] = true;
        }

        return array_slice(array_keys($keywords), 0, 20);
    }

    private function findQuizForOffer(OffreEmploi $offreEmploi): ?Quiz
    {
        foreach ($offreEmploi->getQuizs() as $quiz) {
            return $quiz;
        }

        return null;
    }

    /**
     * @param array{score:int,matched_keywords:array<int,string>,missing_keywords:array<int,string>} $context
     * @return array{
     *     score:int,
     *     summary:string,
     *     recommendation:string,
     *     strengths:array<int,string>,
     *     risks:array<int,string>
     * }|null
     */
    private function buildAdvancedTextAnalysis(OffreEmploi $offreEmploi, string $text, array $context): ?array
    {
        if (!$this->externalAiRecruitmentAnalyzer->isConfigured() || trim($text) === '') {
            return null;
        }

        try {
            return $this->externalAiRecruitmentAnalyzer->analyzeOfferMatch($offreEmploi, $text, $context);
        } catch (\Throwable) {
            return null;
        }
    }
}
