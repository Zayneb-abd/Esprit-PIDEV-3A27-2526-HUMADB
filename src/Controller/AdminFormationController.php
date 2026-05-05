<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/formation')]
class AdminFormationController extends AbstractController
{
    #[Route('/', name: 'admin_formation_index', methods: ['GET'])]
    public function index(Request $request, FormationRepository $formationRepository): Response
    {
        $query = $request->query->get('q');
        $sortField = $request->query->get('sort');
        $sortOrder = $request->query->get('order', 'ASC');

        // Get all formations for statistics
        $allFormations = $formationRepository->findAll();
        
        // Prepare data for charts
        $formationsPerMonth = [];
        $months = [];
        
        foreach ($allFormations as $formation) {
            $month = $formation->getDateDebut() ? $formation->getDateDebut()->format('Y-m') : date('Y-m');
            if (!isset($formationsPerMonth[$month])) {
                $formationsPerMonth[$month] = 0;
                $months[] = date('F', strtotime($month));
            }
            $formationsPerMonth[$month]++;
        }

        return $this->render('admin/formation/index.html.twig', [
            'formations' => $formationRepository->searchAndSort($query, $sortField, $sortOrder),
            'query' => $query,
            'sort' => $sortField,
            'order' => $sortOrder,
            'formationsPerMonth' => $formationsPerMonth,
            'months' => $months,
            'totalFormations' => count($allFormations)
        ]);
    }

    #[Route('/new', name: 'admin_formation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $formation = new Formation();

        $form = $this->createForm(\App\Form\FormationType::class, $formation, [
            'validation_groups' => ['Default', 'creation']
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($formation);
            $entityManager->flush();

            $this->addFlash('success', 'La formation a été créée avec succès.');
            return $this->redirectToRoute('admin_formation_index');
        }

        return $this->render('admin/formation/new.html.twig', [
            'formation' => $formation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_formation_show', methods: ['GET'])]
    public function show(Formation $formation): Response
    {
        return $this->render('admin/formation/show.html.twig', [
            'formation' => $formation,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_formation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Formation $formation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(\App\Form\FormationType::class, $formation, [
            'validation_groups' => ['Default', 'update']
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La formation a été mise à jour avec succès.');
            return $this->redirectToRoute('admin_formation_index');
        }

        return $this->render('admin/formation/edit.html.twig', [
            'formation' => $formation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_formation_delete', methods: ['POST'])]
    public function delete(Request $request, Formation $formation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$formation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($formation);
            $entityManager->flush();
            $this->addFlash('success', 'La formation a été supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_formation_index');
    }

    #[Route('/{id}/accepter', name: 'admin_formation_accepter', methods: ['POST'])]
    public function accepter(Formation $formation, EntityManagerInterface $entityManager): Response
    {
        // This would be for accepting a formation, but formations don't have acceptance status
        // You can implement this logic if needed
        $this->addFlash('info', 'Fonctionnalité à implémenter.');
        return $this->redirectToRoute('admin_formation_index');
    }

    #[Route('/{id}/refuser', name: 'admin_formation_refuser', methods: ['POST'])]
    public function refuser(Formation $formation, EntityManagerInterface $entityManager): Response
    {
        // This would be for refusing a formation, but formations don't have refusal status
        // You can implement this logic if needed
        $this->addFlash('info', 'Fonctionnalité à implémenter.');
        return $this->redirectToRoute('admin_formation_index');
    }
}
