<?php

namespace App\Service;

use App\Entity\OffreEmploi;
use Doctrine\ORM\EntityManagerInterface;

class OfferDeletionService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function deleteOfferWithRelations(OffreEmploi $offreEmploi): void
    {
        foreach ($offreEmploi->getCandidatures()->toArray() as $candidature) {
            foreach ($candidature->getEntretiens()->toArray() as $entretien) {
                $this->entityManager->remove($entretien);
            }

            $this->entityManager->remove($candidature);
        }

        foreach ($offreEmploi->getQuizs()->toArray() as $quiz) {
            foreach ($quiz->getResultatQuizs()->toArray() as $resultatQuiz) {
                $this->entityManager->remove($resultatQuiz);
            }

            foreach ($quiz->getQuestions()->toArray() as $question) {
                foreach ($question->getReponseQcms()->toArray() as $reponseQcm) {
                    $this->entityManager->remove($reponseQcm);
                }

                $this->entityManager->remove($question);
            }

            $this->entityManager->remove($quiz);
        }

        $this->entityManager->remove($offreEmploi);
        $this->entityManager->flush();
    }
}
