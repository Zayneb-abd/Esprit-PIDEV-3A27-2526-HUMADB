<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\ResultatQuiz;
use App\Entity\User;
use App\Repository\ResultatQuizRepository;
use App\Service\QuizEvaluator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/candidat')]
#[IsGranted('ROLE_CANDIDAT')]
class CandidatQuizController extends AbstractController
{
    #[Route('/candidatures/{id}/quiz', name: 'candidat_candidature_quiz', requirements: ['id' => '\d+'])]
    public function takeQuiz(Candidature $candidature, Request $request, ResultatQuizRepository $resultatQuizRepository, EntityManagerInterface $entityManager, QuizEvaluator $quizEvaluator): Response
    {
        $user = $this->getAuthenticatedUser();
        $session = $request->getSession();
        if ($candidature->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $quiz = null;
        foreach ($candidature->getOffreEmploi()?->getQuizs() ?? [] as $offreQuiz) {
            $quiz = $offreQuiz;
            break;
        }

        if ($quiz === null) {
            $this->addFlash('warning', 'Aucun quiz n\'est rattache a cette offre pour le moment.');

            return $this->redirectToRoute('candidat_offer_show', ['id' => $candidature->getOffreEmploi()?->getId()]);
        }

        $existingResult = $resultatQuizRepository->findOneBy([
            'user' => $user,
            'quiz' => $quiz,
        ]);

        $durationMinutes = max(1, (int) ($quiz->getDureeMinutes() ?? 12));
        $durationSeconds = $durationMinutes * 60;
        $sessionKey = 'quiz_started_at_'.$candidature->getId();
        $startedAt = (int) $session->get($sessionKey, 0);

        if (!$existingResult instanceof ResultatQuiz && $startedAt <= 0) {
            $startedAt = time();
            $session->set($sessionKey, $startedAt);
        }

        if ($request->isMethod('POST') && !$existingResult instanceof ResultatQuiz) {
            $submittedAnswers = (array) $request->request->all('answers');
            $effectiveStartedAt = (int) $session->get($sessionKey, time());
            $timeSpent = max(0, time() - $effectiveStartedAt);
            $evaluation = $quizEvaluator->evaluate($quiz, $submittedAnswers);

            $resultat = new ResultatQuiz();
            $resultat->setUser($user);
            $resultat->setQuiz($quiz);
            $resultat->setScorePourcentage((string) $evaluation['score']);
            $resultat->setTempsUtilise(min($timeSpent, $durationSeconds));
            $resultat->setStatut($evaluation['statut']);

            $candidature->setStatut('En cours');
            $candidature->setDateStatut(new \DateTime());

            $entityManager->persist($resultat);
            $entityManager->flush();
            $session->remove($sessionKey);

            if ($timeSpent > $durationSeconds) {
                $this->addFlash('warning', sprintf('Temps ecoule. Le quiz a ete soumis automatiquement avec un score de %.2f%%.', $evaluation['score']));
            } else {
                $this->addFlash('success', sprintf('Quiz termine. Score: %.2f%%.', $evaluation['score']));
            }

            return $this->redirectToRoute('candidat_offer_show', ['id' => $candidature->getOffreEmploi()?->getId()]);
        }

        return $this->render('candidat/quiz/show.html.twig', [
            'candidature' => $candidature,
            'quiz' => $quiz,
            'existing_result' => $existingResult,
            'duration_seconds' => $durationSeconds,
            'quiz_ends_at' => $startedAt + $durationSeconds,
        ]);
    }

    private function getAuthenticatedUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
