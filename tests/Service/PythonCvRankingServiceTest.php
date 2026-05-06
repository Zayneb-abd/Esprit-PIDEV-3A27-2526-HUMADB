<?php

namespace App\Tests\Service;

use App\Entity\OffreEmploi;
use App\Service\PythonCvRankingService;
use PHPUnit\Framework\TestCase;

class PythonCvRankingServiceTest extends TestCase
{
    public function testItRanksTheMostRelevantCvFirst(): void
    {
        $service = new PythonCvRankingService(dirname(__DIR__, 2), 'python3');

        $offre = new OffreEmploi();
        $offre->setTitre('Developpeur Symfony');
        $offre->setDescription('Nous cherchons un developpeur Symfony PHP Doctrine avec API REST.');
        $offre->setDepartement('Informatique');
        $offre->setDatePublication(new \DateTime('today'));
        $offre->setTypeContrat('CDI');
        $offre->setNombrePostes(1);

        $rankings = $service->rankOfferCandidates($offre, [
            'Developpeur Symfony PHP Doctrine, API REST, PHPUnit, MySQL.',
            'Chef de projet marketing digital et communication.',
        ]);

        self::assertCount(2, $rankings);
        self::assertSame(0, $rankings[0]['index']);
        self::assertGreaterThan($rankings[1]['score'], $rankings[0]['score']);
    }
}
