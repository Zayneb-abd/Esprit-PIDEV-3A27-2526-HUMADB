<?php

namespace App\Tests\Entity;

use App\Entity\Candidature;
use App\Entity\OffreEmploi;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class CandidatureTest extends TestCase
{
    public function testItAcceptsAValidApplication(): void
    {
        $candidature = $this->createValidCandidature();

        self::assertSame(0, $this->validator()->validate($candidature)->count());
    }

    public function testItRejectsAnApplicationWithoutStatus(): void
    {
        $candidature = $this->createValidCandidature();
        $candidature->setStatut('');

        $violations = $this->validator()->validate($candidature);

        self::assertGreaterThanOrEqual(1, $violations->count());
        self::assertSame('Le statut est obligatoire.', $violations[0]->getMessage());
    }

    public function testItRejectsAnApplicationWithoutCandidate(): void
    {
        $candidature = $this->createValidCandidature();
        $candidature->setUser(null);

        $violations = $this->validator()->validate($candidature);

        self::assertGreaterThanOrEqual(1, $violations->count());
        self::assertSame('Le candidat est obligatoire.', $violations[0]->getMessage());
    }

    private function createValidCandidature(): Candidature
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Sarah');
        $user->setEmail('sarah.dupont@example.com');
        $user->setMdp('secret');
        $user->setRole('CANDIDAT');

        $offre = new OffreEmploi();
        $offre->setTitre('Developpeur Symfony Senior');
        $offre->setDescription('Nous recherchons un developpeur Symfony Senior pour renforcer notre equipe produit.');
        $offre->setDepartement('Informatique');
        $offre->setDatePublication(new \DateTime('today'));
        $offre->setTypeContrat('CDI');
        $offre->setNombrePostes(2);

        $candidature = new Candidature();
        $candidature->setUser($user);
        $candidature->setOffreEmploi($offre);
        $candidature->setDateCandidature(new \DateTime('today'));
        $candidature->setDateStatut(new \DateTime('today'));
        $candidature->setStatut('En attente');
        $candidature->setCv('cv-sarah-dupont.pdf');

        return $candidature;
    }

    private function validator(): \Symfony\Component\Validator\Validator\ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }
}
