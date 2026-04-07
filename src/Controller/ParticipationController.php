<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Formation;
use App\Entity\Participation;
use App\Repository\FormationRepository;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/employ/participation')]
class ParticipationController extends AbstractController
{
    #[Route('/formations', name: 'employ_participation_formations', methods: ['GET'])]
    public function listFormations(Request $request, FormationRepository $formationRepository): Response
    {
        $query = $request->query->get('q');
        $sortField = $request->query->get('sort');
        $sortOrder = $request->query->get('order', 'ASC');

        // On affiche les formations disponibles à l'inscription
        return $this->render('employ/participation/list_formations.html.twig', [
            'formations' => $formationRepository->searchAndSort($query, $sortField, $sortOrder),
            'query' => $query,
            'sort' => $sortField,
            'order' => $sortOrder,
        ]);
    }

    #[Route('/inscrire/{id}', name: 'employ_participation_inscrire', methods: ['POST'])]
    public function inscrire(Formation $formation, EntityManagerInterface $entityManager, ParticipationRepository $participationRepository): Response
    {
        // Simuler un utilisateur avec ID 1
        $user = $entityManager->getRepository(User::class)->find(1);
        
        if (!$user) {
            $this->addFlash('danger', 'Utilisateur ID 1 non trouvé dans la base de données.');
            return $this->redirectToRoute('employ_participation_formations');
        }

        // Vérifier si l'employé est déjà inscrit à cette formation
        $existingParticipation = $participationRepository->findOneBy([
            'user' => $user,
            'formation' => $formation
        ]);

        if ($existingParticipation) {
            $this->addFlash('warning', 'Vous êtes déjà inscrit à cette formation (Statut : ' . $existingParticipation->getStatut() . ').');
        } else {
            $participation = new Participation();
            $participation->setUser($user);
            $participation->setFormation($formation);
            $participation->setDateInscription(new \DateTime());
            $participation->setStatut('en attente');

            $entityManager->persist($participation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre demande d\'inscription à la formation "' . $formation->getSujet() . '" a été envoyée.');
        }

        return $this->redirectToRoute('employ_participation_formations');
    }

    #[Route('/mes-participations', name: 'employ_participation_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager, ParticipationRepository $participationRepository): Response
    {
        // Simuler un utilisateur avec ID 1
        $user = $entityManager->getRepository(User::class)->find(1);

        if (!$user) {
            return $this->render('employ/participation/index.html.twig', [
                'participations' => [],
            ]);
        }

        $query = $request->query->get('q');
        $sortField = $request->query->get('sort');
        $sortOrder = $request->query->get('order', 'ASC');

        return $this->render('employ/participation/index.html.twig', [
            'participations' => $participationRepository->searchAndSort($query, $sortField, $sortOrder, $user->getId()),
            'query' => $query,
            'sort' => $sortField,
            'order' => $sortOrder,
        ]);
    }
}
