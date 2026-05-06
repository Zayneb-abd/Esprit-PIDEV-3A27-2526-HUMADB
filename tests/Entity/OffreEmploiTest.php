<?php

namespace App\Tests\Entity;

use App\Entity\OffreEmploi;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class OffreEmploiTest extends TestCase
{
    public function testItAcceptsAValidOffer(): void
    {
        $offre = $this->createValidOffer();

        self::assertSame(0, $this->validator()->validate($offre)->count());
    }

    public function testItRejectsAnOfferWithoutAJobTitle(): void
    {
        $offre = $this->createValidOffer();
        $offre->setTitre('');

        $violations = $this->validator()->validate($offre);

        self::assertGreaterThanOrEqual(1, $violations->count());
        self::assertSame("Le titre de l'offre est obligatoire.", $violations[0]->getMessage());
    }

    public function testItRejectsAnOfferWithAnInvalidContractType(): void
    {
        $offre = $this->createValidOffer();
        $offre->setTypeContrat('INTERNSHIP');

        $violations = $this->validator()->validate($offre);

        self::assertGreaterThanOrEqual(1, $violations->count());
        self::assertSame("Le type de contrat n'est pas valide.", $violations[0]->getMessage());
    }

    private function createValidOffer(): OffreEmploi
    {
        $offre = new OffreEmploi();
        $offre->setTitre('Developpeur Symfony Senior');
        $offre->setDescription('Nous recherchons un developpeur Symfony Senior pour renforcer notre equipe produit.');
        $offre->setDepartement('Informatique');
        $offre->setDatePublication(new \DateTime('today'));
        $offre->setTypeContrat('CDI');
        $offre->setNombrePostes(2);

        return $offre;
    }

    private function validator(): \Symfony\Component\Validator\Validator\ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }
}
