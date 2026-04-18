<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\Entretien;
use App\Entity\OffreEmploi;
use App\Entity\Quiz;
use App\Entity\ResultatQuiz;
use App\Entity\User;
use App\Form\CandidatPostulationType;
use App\Repository\CandidatureRepository;
use App\Repository\EntretienRepository;
use App\Repository\OffreEmploiRepository;
use App\Repository\ResultatQuizRepository;
use App\Repository\QuizRepository;
use App\Service\CandidateCvManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/candidat')]
#[IsGranted('ROLE_CANDIDAT')]
class CondidatController extends AbstractController
{
    #[Route('', name: 'candidat_dashboard')]
    public function dashboard(OffreEmploiRepository $offreEmploiRepository, CandidatureRepository $candidatureRepository, EntretienRepository $entretienRepository, ResultatQuizRepository $resultatQuizRepository): Response
    {
        $user = $this->getAuthenticatedUser();
        $offres = $offreEmploiRepository->findBy([], ['date_publication' => 'DESC', 'id' => 'DESC']);
        $candidatures = $candidatureRepository->findBy(['user' => $user], ['date_candidature' => 'DESC', 'id' => 'DESC']);
        $entretiens = $entretienRepository->createQueryBuilder('e')
            ->leftJoin('e.candidature', 'c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('e.date_entretien', 'ASC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        $entretiensCount = (int) $entretienRepository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->leftJoin('e.candidature', 'c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        $quizResults = $resultatQuizRepository->findBy(['user' => $user], ['id' => 'DESC'], 3);

        return $this->render('candidat/dashboard/index.html.twig', [
            'stats' => [
                'offres' => count($offres),
                'postulations' => count($candidatures),
                'en_attente' => count(array_filter($candidatures, static fn (Candidature $candidature): bool => $candidature->getStatut() === 'En attente')),
                'acceptees' => count(array_filter($candidatures, static fn (Candidature $candidature): bool => $candidature->getStatut() === 'Acceptee')),
                'entretiens' => $entretiensCount,
            ],
            'offres_recentes' => array_slice($offres, 0, 4),
            'candidatures_recentes' => array_slice($candidatures, 0, 5),
            'entretiens_a_venir' => $entretiens,
            'quiz_recents' => $quizResults,
        ]);
    }

    #[Route('/inventory', name: 'candidat_inventory')]
    public function inventory(OffreEmploiRepository $offreEmploiRepository, CandidatureRepository $candidatureRepository): Response
    {
        $user = $this->getAuthenticatedUser();
        $offres = $offreEmploiRepository->findBy([], ['date_publication' => 'DESC', 'id' => 'DESC']);
        $candidatures = $candidatureRepository->findBy(['user' => $user]);

        $postulationsParOffre = [];
        foreach ($candidatures as $candidature) {
            if ($candidature->getOffreEmploi()) {
                $postulationsParOffre[$candidature->getOffreEmploi()->getId()] = $candidature;
            }
        }

        return $this->render('candidat/inventory/index.html.twig', [
            'offres' => $offres,
            'postulations_par_offre' => $postulationsParOffre,
        ]);
    }

    #[Route('/product/create', name: 'candidat_product_create')]
    public function createProduct(Request $request, EntityManagerInterface $entityManager, CandidateCvManager $candidateCvManager): Response
    {
        $user = $this->getAuthenticatedUser();
        $candidature = new Candidature();
        $candidature->setUser($user);
        $candidature->setDateCandidature(new \DateTime());
        $candidature->setDateStatut(new \DateTime());
        $candidature->setStatut('En attente');

        $form = $this->createForm(CandidatPostulationType::class, $candidature, [
            'include_offer' => true,
            'require_cv' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->hasAlreadyApplied($user, $candidature->getOffreEmploi(), $entityManager)) {
                $this->addFlash('warning', 'Vous avez deja postule a cette offre.');

                return $this->redirectToRoute('candidat_reports');
            }

            $uploadedCv = $form->get('cvFile')->getData();
            if ($uploadedCv === null) {
                $this->addFlash('danger', 'Veuillez televerser votre CV PDF avant de continuer.');

                return $this->render('candidat/product/create.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            try {
                $candidature->setCv($candidateCvManager->storeUploadedApplicationCv($uploadedCv));
            } catch (FileException $exception) {
                $this->addFlash('danger', 'Le televersement du CV a echoue. Verifiez les permissions du dossier uploads et reessayez.');

                return $this->render('candidat/product/create.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $entityManager->persist($candidature);
            $entityManager->flush();

            if ($this->findQuizForOffer($candidature->getOffreEmploi()) instanceof Quiz) {
                $this->addFlash('success', 'Etape 1 terminee: CV envoye. Passez maintenant le quiz.');

                return $this->redirectToRoute('candidat_candidature_quiz', ['id' => $candidature->getId()]);
            }

            $this->addFlash('success', 'Votre candidature a bien ete envoyee.');

            return $this->redirectToRoute('candidat_reports');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Le formulaire contient des erreurs. Le CV doit etre un fichier PDF de 5 Mo maximum.');
        }

        return $this->render('candidat/product/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reports', name: 'candidat_reports')]
    public function reports(CandidatureRepository $candidatureRepository, ResultatQuizRepository $resultatQuizRepository): Response
    {
        $user = $this->getAuthenticatedUser();
        $candidatures = $candidatureRepository->findBy(['user' => $user], ['date_candidature' => 'DESC', 'id' => 'DESC']);
        $resultatsParQuiz = [];
        $entretiensParCandidature = [];

        foreach ($candidatures as $candidature) {
            $quiz = $this->findQuizForOffer($candidature->getOffreEmploi());
            if ($quiz instanceof Quiz) {
                $resultatsParQuiz[$candidature->getId()] = $resultatQuizRepository->findOneBy([
                    'user' => $user,
                    'quiz' => $quiz,
                ]);
            }

            $entretiens = $candidature->getEntretiens()->toArray();
            usort($entretiens, static fn (Entretien $left, Entretien $right): int => ($right->getDateEntretien()?->getTimestamp() ?? 0) <=> ($left->getDateEntretien()?->getTimestamp() ?? 0));
            $entretiensParCandidature[$candidature->getId()] = $entretiens[0] ?? null;
        }

        return $this->render('candidat/reports/index.html.twig', [
            'candidatures' => $candidatures,
            'resultats_par_candidature' => $resultatsParQuiz,
            'entretiens_par_candidature' => $entretiensParCandidature,
        ]);
    }

    #[Route('/docs', name: 'candidat_docs')]
    public function docs(): Response
    {
        return $this->render('candidat/docs/index.html.twig');
    }

    #[Route('/offres/{id}', name: 'candidat_offer_show', requirements: ['id' => '\d+'])]
    public function showOffer(OffreEmploi $offreEmploi, CandidatureRepository $candidatureRepository, ResultatQuizRepository $resultatQuizRepository): Response
    {
        $user = $this->getAuthenticatedUser();
        $candidature = $candidatureRepository->findOneBy([
            'user' => $user,
            'offreEmploi' => $offreEmploi,
        ]);
        $quiz = $this->findQuizForOffer($offreEmploi);
        $resultatQuiz = null;
        $latestEntretien = null;

        if ($quiz instanceof Quiz) {
            $resultatQuiz = $resultatQuizRepository->findOneBy([
                'user' => $user,
                'quiz' => $quiz,
            ]);
        }

        if ($candidature instanceof Candidature) {
            $entretiens = $candidature->getEntretiens()->toArray();
            usort($entretiens, static fn (Entretien $left, Entretien $right): int => ($right->getDateEntretien()?->getTimestamp() ?? 0) <=> ($left->getDateEntretien()?->getTimestamp() ?? 0));
            $latestEntretien = $entretiens[0] ?? null;
        }

        return $this->render('candidat/offre/show.html.twig', [
            'offre' => $offreEmploi,
            'candidature' => $candidature,
            'quiz' => $quiz,
            'resultat_quiz' => $resultatQuiz,
            'latest_entretien' => $latestEntretien,
        ]);
    }

    #[Route('/offres/{id}/postuler', name: 'candidat_offer_apply', requirements: ['id' => '\d+'])]
    public function applyToOffer(Request $request, OffreEmploi $offreEmploi, CandidatureRepository $candidatureRepository, EntityManagerInterface $entityManager, CandidateCvManager $candidateCvManager): Response
    {
        $user = $this->getAuthenticatedUser();

        $existing = $candidatureRepository->findOneBy([
            'user' => $user,
            'offreEmploi' => $offreEmploi,
        ]);

        if ($existing instanceof Candidature) {
            $this->addFlash('warning', 'Vous avez deja postule a cette offre.');

            return $this->redirectToRoute('candidat_offer_show', ['id' => $offreEmploi->getId()]);
        }

        $candidature = new Candidature();
        $candidature->setUser($user);
        $candidature->setOffreEmploi($offreEmploi);
        $candidature->setDateCandidature(new \DateTime());
        $candidature->setDateStatut(new \DateTime());
        $candidature->setStatut('En attente');

        $form = $this->createForm(CandidatPostulationType::class, $candidature, [
            'include_offer' => false,
            'require_cv' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedCv = $form->get('cvFile')->getData();
            if ($uploadedCv === null) {
                $this->addFlash('danger', 'Veuillez televerser votre CV PDF avant de continuer.');

                return $this->render('candidat/offre/apply.html.twig', [
                    'offre' => $offreEmploi,
                    'form' => $form->createView(),
                ]);
            }

            try {
                $candidature->setCv($candidateCvManager->storeUploadedApplicationCv($uploadedCv));
            } catch (FileException $exception) {
                $this->addFlash('danger', 'Le televersement du CV a echoue. Verifiez les permissions du dossier uploads et reessayez.');

                return $this->render('candidat/offre/apply.html.twig', [
                    'offre' => $offreEmploi,
                    'form' => $form->createView(),
                ]);
            }

            $entityManager->persist($candidature);
            $entityManager->flush();

            if ($this->findQuizForOffer($offreEmploi) instanceof Quiz) {
                $this->addFlash('success', 'Etape 1 terminee: CV envoye. Passez maintenant le quiz dans le temps imparti.');

                return $this->redirectToRoute('candidat_candidature_quiz', ['id' => $candidature->getId()]);
            }

            $this->addFlash('success', 'Votre candidature a bien ete envoyee.');

            return $this->redirectToRoute('candidat_reports');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Le formulaire contient des erreurs. Le CV doit etre un fichier PDF de 5 Mo maximum.');
        }

        return $this->render('candidat/offre/apply.html.twig', [
            'offre' => $offreEmploi,
            'form' => $form->createView(),
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

    private function hasAlreadyApplied(User $user, ?OffreEmploi $offreEmploi, EntityManagerInterface $entityManager): bool
    {
        if (!$offreEmploi instanceof OffreEmploi) {
            return false;
        }

        return $entityManager->getRepository(Candidature::class)->findOneBy([
            'user' => $user,
            'offreEmploi' => $offreEmploi,
        ]) instanceof Candidature;
    }

    private function findQuizForOffer(?OffreEmploi $offreEmploi): ?Quiz
    {
        if (!$offreEmploi instanceof OffreEmploi) {
            return null;
        }

        foreach ($offreEmploi->getQuizs() as $quiz) {
            return $quiz;
        }

        return null;
    }
}
