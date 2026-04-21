<?php

namespace App\Controller;

use App\Entity\Participation;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/participation')]
class AdminParticipationController extends AbstractController
{
    #[Route('/', name: 'admin_participation_index', methods: ['GET'])]
    public function index(Request $request, ParticipationRepository $participationRepository): Response
    {
        $query = $request->query->get('q');
        $sortField = $request->query->get('sort');
        $sortOrder = $request->query->get('order', 'ASC');

        // Get participation statistics for charts
        $allParticipations = $participationRepository->findAll();
        
        // Prepare data for bar chart: participants per training
        $participantsPerTraining = [];
        $trainingLabels = [];
        $trainings = [];
        
        foreach ($allParticipations as $participation) {
            $formation = $participation->getFormation();
            if ($formation) {
                $formationId = $formation->getId();
                if (!isset($participantsPerTraining[$formationId])) {
                    $participantsPerTraining[$formationId] = 0;
                    $trainingLabels[$formationId] = $formation->getSujet() ?? 'Formation ' . $formationId;
                    $trainings[$formationId] = $formation;
                }
                $participantsPerTraining[$formationId]++;
            }
        }
        
        // Prepare data for pie chart: results distribution
        $resultsDistribution = [
            'accepté' => 0,
            'refusé' => 0,
            'en attente' => 0
        ];
        
        foreach ($allParticipations as $participation) {
            $statut = $participation->getStatut() ?? 'en attente';
            if (isset($resultsDistribution[$statut])) {
                $resultsDistribution[$statut]++;
            }
        }

        return $this->render('admin/participation/index.html.twig', [
            'participations' => $participationRepository->searchAndSort($query, $sortField, $sortOrder),
            'query' => $query,
            'sort' => $sortField,
            'order' => $sortOrder,
            'participantsPerTraining' => array_values($participantsPerTraining),
            'trainingLabels' => array_values($trainingLabels),
            'resultsDistribution' => $resultsDistribution,
        ]);
    }

    #[Route('/accepter/{id}', name: 'admin_participation_accepter', methods: ['POST'])]
    public function accepter(Participation $participation, EntityManagerInterface $entityManager): Response
    {
        $participation->setStatut('accepté');
        $entityManager->flush();

        $this->addFlash('success', 'La participation de ' . $participation->getUser()->getNom() . ' a été acceptée.');

        return $this->redirectToRoute('admin_participation_index');
    }

    #[Route('/refuser/{id}', name: 'admin_participation_refuser', methods: ['POST'])]
    public function refuser(Participation $participation, EntityManagerInterface $entityManager): Response
    {
        $participation->setStatut('refusé');
        $entityManager->flush();

        $this->addFlash('warning', 'La participation de ' . $participation->getUser()->getNom() . ' a été refusée.');

        return $this->redirectToRoute('admin_participation_index');
    }

    #[Route('/supprimer/{id}', name: 'admin_participation_supprimer', methods: ['POST'])]
    public function supprimer(Request $request, Participation $participation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$participation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($participation);
            $entityManager->flush();
            $this->addFlash('danger', 'La participation a été supprimée.');
        }

        return $this->redirectToRoute('admin_participation_index');
    }

    #[Route('/voir/{id}', name: 'admin_participation_voir', methods: ['GET'])]
    public function voir(Participation $participation): Response
    {
        return $this->render('admin/participation/show.html.twig', [
            'participation' => $participation,
        ]);
    }

    #[Route('/modifier/{id}', name: 'admin_participation_modifier', methods: ['GET', 'POST'])]
    public function modifier(Request $request, Participation $participation, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $statut = $request->request->get('statut');
            if ($statut && in_array($statut, ['en attente', 'accepté', 'refusé'])) {
                $participation->setStatut($statut);
                $entityManager->flush();
                $this->addFlash('success', 'La participation a été modifiée.');
                return $this->redirectToRoute('admin_participation_index');
            }
        }

        return $this->render('admin/participation/edit.html.twig', [
            'participation' => $participation,
        ]);
    }
}
