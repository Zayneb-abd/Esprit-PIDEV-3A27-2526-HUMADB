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

        return $this->render('admin/participation/index.html.twig', [
            'participations' => $participationRepository->searchAndSort($query, $sortField, $sortOrder),
            'query' => $query,
            'sort' => $sortField,
            'order' => $sortOrder,
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
}
