<?php

namespace App\Tests\Service;

use App\Entity\Candidature;
use App\Entity\OffreEmploi;
use App\Entity\User;
use App\Repository\ResultatQuizRepository;
use App\Service\CandidateCvManager;
use App\Service\CvMatchingService;
use App\Service\ExternalAiRecruitmentAnalyzer;
use App\Service\PdfCvPreviewService;
use App\Service\PythonCvRankingService;
use PHPUnit\Framework\TestCase;

class CvMatchingServiceTest extends TestCase
{
    public function testItUsesPythonMlScoresToRankCandidates(): void
    {
        $candidateCvManager = $this->createMock(CandidateCvManager::class);
        $candidateCvManager->method('getApplicationCvAbsolutePath')
            ->willReturnMap([
                ['cv-1.pdf', '/tmp/cv-1.pdf'],
                ['cv-2.pdf', '/tmp/cv-2.pdf'],
            ]);

        $pdfPreviewService = $this->createMock(PdfCvPreviewService::class);
        $pdfPreviewService->method('extractPreview')
            ->willReturnCallback(static function (?string $path): ?string {
                return str_contains((string) $path, 'cv-1') ? 'Symfony PHP Doctrine API REST' : 'Marketing communication sales';
            });

        $pythonRankingService = $this->createMock(PythonCvRankingService::class);
        $pythonRankingService->method('rankOfferCandidates')
            ->willReturn([
                [
                    'index' => 0,
                    'score' => 92,
                    'matched_terms' => ['symfony', 'php', 'doctrine'],
                    'missing_terms' => ['api'],
                    'similarity' => 0.92,
                ],
                [
                    'index' => 1,
                    'score' => 14,
                    'matched_terms' => ['communication'],
                    'missing_terms' => ['symfony'],
                    'similarity' => 0.14,
                ],
            ]);

        $resultatQuizRepository = $this->createMock(ResultatQuizRepository::class);
        $resultatQuizRepository->method('findOneBy')->willReturn(null);

        $externalAiRecruitmentAnalyzer = $this->createMock(ExternalAiRecruitmentAnalyzer::class);
        $externalAiRecruitmentAnalyzer->method('isConfigured')->willReturn(false);

        $service = new CvMatchingService(
            $candidateCvManager,
            $pdfPreviewService,
            $pythonRankingService,
            $resultatQuizRepository,
            $externalAiRecruitmentAnalyzer
        );

        $offre = new OffreEmploi();
        $offre->setTitre('Developpeur Symfony');
        $offre->setDescription('Poste Symfony PHP Doctrine API REST.');
        $offre->setDepartement('Informatique');
        $offre->setDatePublication(new \DateTime('today'));
        $offre->setTypeContrat('CDI');
        $offre->setNombrePostes(1);

        $firstUser = new User();
        $firstUser->setNom('Dupont');
        $firstUser->setPrenom('Sarah');
        $firstUser->setEmail('sarah@example.com');
        $firstUser->setMdp('secret');
        $firstUser->setRole('CANDIDAT');

        $secondUser = new User();
        $secondUser->setNom('Martin');
        $secondUser->setPrenom('Paul');
        $secondUser->setEmail('paul@example.com');
        $secondUser->setMdp('secret');
        $secondUser->setRole('CANDIDAT');

        $firstCandidature = new Candidature();
        $firstCandidature->setUser($firstUser);
        $firstCandidature->setCv('cv-1.pdf');

        $secondCandidature = new Candidature();
        $secondCandidature->setUser($secondUser);
        $secondCandidature->setCv('cv-2.pdf');

        $ranked = $service->rankForOffer($offre, [$firstCandidature, $secondCandidature]);

        self::assertCount(2, $ranked);
        self::assertSame($firstCandidature, $ranked[0]['candidature']);
        self::assertSame('python_ml', $ranked[0]['analysis_mode']);
        self::assertSame(92, $ranked[0]['score']);
        self::assertSame($secondCandidature, $ranked[1]['candidature']);
    }
}
