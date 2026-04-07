<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\OffreEmploi;
use App\Entity\User;
use App\Form\CandidatureType;
use App\Form\OffreEmploiType;
use App\Repository\CandidatureRepository;
use App\Repository\OffreEmploiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard/index.html.twig');
    }

    #[Route('/inventory', name: 'admin_inventory')]
    public function inventory(OffreEmploiRepository $offreEmploiRepository, CandidatureRepository $candidatureRepository): Response
    {
        $offres = $offreEmploiRepository->findBy([], ['date_publication' => 'DESC', 'id' => 'DESC']);
        $candidatures = $candidatureRepository->findBy([], ['date_candidature' => 'DESC', 'id' => 'DESC']);

        $totalPostes = array_reduce(
            $offres,
            static fn (int $carry, OffreEmploi $offre): int => $carry + ($offre->getNombrePostes() ?? 0),
            0
        );

        $candidaturesEnAttente = count(array_filter(
            $candidatures,
            static fn (Candidature $candidature): bool => $candidature->getStatut() === 'En attente'
        ));

        return $this->render('admin/inventory/index.html.twig', [
            'offres' => $offres,
            'candidatures' => $candidatures,
            'stats' => [
                'offres' => count($offres),
                'candidatures' => count($candidatures),
                'postes' => $totalPostes,
                'candidatures_en_attente' => $candidaturesEnAttente,
            ],
        ]);
    }

    #[Route('/offres/new', name: 'admin_offre_new')]
    public function newOffre(Request $request, EntityManagerInterface $entityManager): Response
    {
        $offre = new OffreEmploi();
        $offre->setDatePublication(new \DateTimeImmutable());

        $user = $this->getUser();
        if ($user instanceof User) {
            $offre->setUser($user);
        }

        $form = $this->createForm(OffreEmploiType::class, $offre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($offre);
            $entityManager->flush();

            $this->addFlash('success', "L'offre d'emploi a ete creee.");

            return $this->redirectToRoute('admin_offre_show', ['id' => $offre->getId()]);
        }

        return $this->render('admin/offre_emploi/new.html.twig', [
            'form' => $form->createView(),
            'offre' => $offre,
        ]);
    }

    #[Route('/offres/{id}', name: 'admin_offre_show', requirements: ['id' => '\d+'])]
    public function showOffre(OffreEmploi $offre): Response
    {
        return $this->render('admin/offre_emploi/show.html.twig', [
            'offre' => $offre,
        ]);
    }

    #[Route('/offres/{id}/edit', name: 'admin_offre_edit', requirements: ['id' => '\d+'])]
    public function editOffre(Request $request, OffreEmploi $offre, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OffreEmploiType::class, $offre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$offre->getUser()) {
                $user = $this->getUser();
                if ($user instanceof User) {
                    $offre->setUser($user);
                }
            }

            $entityManager->flush();

            $this->addFlash('success', "L'offre d'emploi a ete mise a jour.");

            return $this->redirectToRoute('admin_offre_show', ['id' => $offre->getId()]);
        }

        return $this->render('admin/offre_emploi/edit.html.twig', [
            'form' => $form->createView(),
            'offre' => $offre,
        ]);
    }

    #[Route('/offres/{id}/delete', name: 'admin_offre_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteOffre(Request $request, OffreEmploi $offre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_offre_'.$offre->getId(), (string) $request->request->get('_token'))) {
            if ($offre->getCandidatures()->count() > 0 || $offre->getQuizs()->count() > 0) {
                $this->addFlash('danger', "Suppression impossible: l'offre est liee a des candidatures ou des quiz.");
            } else {
                $entityManager->remove($offre);
                $entityManager->flush();
                $this->addFlash('success', "L'offre d'emploi a ete supprimee.");
            }
        }

        return $this->redirectToRoute('admin_inventory');
    }

    #[Route('/candidatures/new', name: 'admin_candidature_new')]
    public function newCandidature(Request $request, EntityManagerInterface $entityManager): Response
    {
        $candidature = new Candidature();
        $candidature->setDateCandidature(new \DateTimeImmutable());
        $candidature->setDateStatut(new \DateTimeImmutable());
        $candidature->setStatut('En attente');

        $form = $this->createForm(CandidatureType::class, $candidature);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($candidature);
            $entityManager->flush();

            $this->addFlash('success', 'La candidature a ete creee.');

            return $this->redirectToRoute('admin_candidature_show', ['id' => $candidature->getId()]);
        }

        return $this->render('admin/candidature/new.html.twig', [
            'form' => $form->createView(),
            'candidature' => $candidature,
        ]);
    }

    #[Route('/candidatures/{id}', name: 'admin_candidature_show', requirements: ['id' => '\d+'])]
    public function showCandidature(Candidature $candidature): Response
    {
        return $this->render('admin/candidature/show.html.twig', [
            'candidature' => $candidature,
        ]);
    }

    #[Route('/candidatures/{id}/edit', name: 'admin_candidature_edit', requirements: ['id' => '\d+'])]
    public function editCandidature(Request $request, Candidature $candidature, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CandidatureType::class, $candidature);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La candidature a ete mise a jour.');

            return $this->redirectToRoute('admin_candidature_show', ['id' => $candidature->getId()]);
        }

        return $this->render('admin/candidature/edit.html.twig', [
            'form' => $form->createView(),
            'candidature' => $candidature,
        ]);
    }

    #[Route('/candidatures/{id}/delete', name: 'admin_candidature_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteCandidature(Request $request, Candidature $candidature, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_candidature_'.$candidature->getId(), (string) $request->request->get('_token'))) {
            if ($candidature->getEntretiens()->count() > 0) {
                $this->addFlash('danger', 'Suppression impossible: la candidature est liee a des entretiens.');
            } else {
                $entityManager->remove($candidature);
                $entityManager->flush();
                $this->addFlash('success', 'La candidature a ete supprimee.');
            }
        }

        return $this->redirectToRoute('admin_inventory');
    }

    #[Route('/product/create', name: 'admin_product_create')]
    public function createProduct(): Response
    {
        return $this->render('admin/product/create.html.twig');
    }

    #[Route('/reports', name: 'admin_reports')]
    public function reports(): Response
    {
        return $this->render('admin/reports/index.html.twig');
    }

    #[Route('/docs', name: 'admin_docs')]
    public function docs(): Response
    {
        return $this->render('admin/docs/index.html.twig');
    }
}
