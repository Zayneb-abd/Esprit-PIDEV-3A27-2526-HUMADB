<?php

namespace App\Tests\Service;

use App\Entity\Candidature;
use App\Entity\Entretien;
use App\Entity\OffreEmploi;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\ReponseQcm;
use App\Entity\ResultatQuiz;
use App\Service\OfferDeletionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class OfferDeletionServiceTest extends TestCase
{
    public function testItDeletesOfferGraphAndFlushesOnce(): void
    {
        $offre = $this->createOfferWithRelations();
        $removed = [];

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(7))
            ->method('remove')
            ->willReturnCallback(static function (object $entity) use (&$removed): void {
                $removed[] = $entity::class;
            });
        $entityManager->expects(self::once())
            ->method('flush');

        $service = new OfferDeletionService($entityManager);
        $service->deleteOfferWithRelations($offre);

        self::assertSame([
            Entretien::class,
            Candidature::class,
            ResultatQuiz::class,
            ReponseQcm::class,
            Question::class,
            Quiz::class,
            OffreEmploi::class,
        ], $removed);
    }

    private function createOfferWithRelations(): OffreEmploi
    {
        $offre = new OffreEmploi();
        $offre->setTitre('Developpeur Symfony Senior');
        $offre->setDescription('Nous recherchons un developpeur Symfony Senior pour renforcer notre equipe produit.');
        $offre->setDepartement('Informatique');
        $offre->setDatePublication(new \DateTime('today'));
        $offre->setTypeContrat('CDI');
        $offre->setNombrePostes(2);

        $candidature = new Candidature();
        $candidature->setDateCandidature(new \DateTime('today'));
        $candidature->setDateStatut(new \DateTime('today'));
        $candidature->setStatut('En attente');
        $candidature->setCv('cv.pdf');
        $offre->addCandidature($candidature);

        $entretien = new Entretien();
        $candidature->addEntretien($entretien);

        $quiz = new Quiz();
        $quiz->setTitre('Quiz technique');
        $offre->addQuiz($quiz);

        $resultatQuiz = new ResultatQuiz();
        $quiz->addResultatQuiz($resultatQuiz);

        $question = new Question();
        $question->setQuestionText('Quelle est la difference entre service et repository ?');
        $quiz->addQuestion($question);

        $reponseQcm = new ReponseQcm();
        $reponseQcm->setTexte('Le service porte la logique metier.');
        $question->addReponseQcm($reponseQcm);

        return $offre;
    }
}
