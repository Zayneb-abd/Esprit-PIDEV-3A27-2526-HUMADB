<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\OffreEmploi;
use App\Entity\User;
use App\Form\CandidatPostulationType;
use App\Repository\CandidatureRepository;
use App\Repository\OffreEmploiRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    public function dashboard(OffreEmploiRepository $offreEmploiRepository, CandidatureRepository $candidatureRepository): Response
    {
        $user = $this->getAuthenticatedUser();
        $offres = $offreEmploiRepository->findBy([], ['date_publication' => 'DESC', 'id' => 'DESC']);
        $candidatures = $candidatureRepository->findBy(['user' => $user], ['date_candidature' => 'DESC', 'id' => 'DESC']);

        return $this->render('candidat/dashboard/index.html.twig', [
            'stats' => [
                'offres' => count($offres),
                'postulations' => count($candidatures),
                'en_attente' => count(array_filter($candidatures, static fn (Candidature $candidature): bool => $candidature->getStatut() === 'En attente')),
                'acceptees' => count(array_filter($candidatures, static fn (Candidature $candidature): bool => $candidature->getStatut() === 'Acceptee')),
            ],
            'offres_recentes' => array_slice($offres, 0, 4),
            'candidatures_recentes' => array_slice($candidatures, 0, 5),
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
    public function createProduct(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getAuthenticatedUser();
        $candidature = new Candidature();
        $candidature->setUser($user);
        $candidature->setDateCandidature(new \DateTime());
        $candidature->setDateStatut(new \DateTime());
        $candidature->setStatut('En attente');

        $form = $this->createForm(CandidatPostulationType::class, $candidature);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->hasAlreadyApplied($user, $candidature->getOffreEmploi(), $entityManager)) {
                $this->addFlash('warning', 'Vous avez deja postule a cette offre.');

                return $this->redirectToRoute('candidat_reports');
            }

            $entityManager->persist($candidature);
            $entityManager->flush();

            $this->addFlash('success', 'Votre candidature a bien ete envoyee.');

            return $this->redirectToRoute('candidat_reports');
        }

        return $this->render('candidat/product/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reports', name: 'candidat_reports')]
    public function reports(CandidatureRepository $candidatureRepository): Response
    {
        $user = $this->getAuthenticatedUser();

        return $this->render('candidat/reports/index.html.twig', [
            'candidatures' => $candidatureRepository->findBy(['user' => $user], ['date_candidature' => 'DESC', 'id' => 'DESC']),
        ]);
    }

    #[Route('/docs', name: 'candidat_docs')]
    public function docs(): Response
    {
        return $this->render('candidat/docs/index.html.twig');
    }

    #[Route('/offres/{id}', name: 'candidat_offer_show', requirements: ['id' => '\d+'])]
    public function showOffer(OffreEmploi $offreEmploi, CandidatureRepository $candidatureRepository): Response
    {
        $user = $this->getAuthenticatedUser();
        $candidature = $candidatureRepository->findOneBy([
            'user' => $user,
            'offreEmploi' => $offreEmploi,
        ]);

        return $this->render('candidat/offre/show.html.twig', [
            'offre' => $offreEmploi,
            'candidature' => $candidature,
        ]);
    }

    #[Route('/offres/{id}/postuler', name: 'candidat_offer_apply', requirements: ['id' => '\d+'])]
    public function applyToOffer(Request $request, OffreEmploi $offreEmploi, CandidatureRepository $candidatureRepository, EntityManagerInterface $entityManager): Response
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

        $form = $this->createForm(CandidatPostulationType::class, $candidature);
        $form->remove('offreEmploi');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($candidature);
            $entityManager->flush();

            $this->addFlash('success', 'Votre candidature a bien ete envoyee.');

            return $this->redirectToRoute('candidat_reports');
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
}
