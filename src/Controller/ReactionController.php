<?php

namespace App\Controller;

use App\Entity\ReactionPublication;
use App\Repository\ReactionPublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reaction')]
class ReactionController extends AbstractController
{
    private $reactionRepository;
    private $entityManager;

    public function __construct(ReactionPublicationRepository $reactionRepository, EntityManagerInterface $entityManager)
    {
        $this->reactionRepository = $reactionRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Ajouter ou mettre à jour une réaction (like/dislike)
     */
    #[Route('/add/{publicationId}', name: 'reaction_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addReaction(int $publicationId, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'error' => 'Utilisateur non connecté'], 401);
        }

        $type = strtolower(trim((string) $request->request->get('type')));
        
        if (!in_array($type, [ReactionPublication::TYPE_LIKE, ReactionPublication::TYPE_DISLIKE])) {
            return new JsonResponse(['success' => false, 'error' => 'Type de réaction invalide'], 400);
        }

        try {
            $reaction = $this->reactionRepository->addOrUpdateReaction(
                $publicationId, 
                $user->getId(), 
                $type
            );

            // Obtenir les nouveaux comptes
            $counts = $this->reactionRepository->getReactionsCountForPublication($publicationId);
            
            // Obtenir la réaction actuelle de l'utilisateur
            $userReaction = null;
            if ($reaction) {
                $userReaction = [
                    'type' => $reaction->getType(),
                    'emoji' => $reaction->getEmoji()
                ];
            }

            return new JsonResponse([
                'success' => true,
                'counts' => [
                    'like' => $counts['like'],
                    'dislike' => $counts['dislike']
                ],
                'userReaction' => $userReaction,
                'message' => $reaction ? 'Réaction ajoutée avec succès' : 'Réaction supprimée'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false, 
                'error' => 'Erreur lors de la gestion de la réaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les comptes de réactions pour une publication
     */
    #[Route('/counts/{publicationId}', name: 'reaction_counts', methods: ['GET'])]
    public function getReactionCounts(int $publicationId): JsonResponse
    {
        try {
            $counts = $this->reactionRepository->getReactionsCountForPublication($publicationId);
            
            return new JsonResponse([
                'success' => true,
                'counts' => $counts
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false, 
                'error' => 'Erreur lors de la récupération des réactions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir la réaction d'un utilisateur pour une publication
     */
    #[Route('/user/{publicationId}', name: 'reaction_user', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getUserReaction(int $publicationId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'error' => 'Utilisateur non connecté'], 401);
        }

        try {
            $reaction = $this->reactionRepository->getUserReactionForPublication(
                $publicationId, 
                $user->getId()
            );

            $userReaction = null;
            if ($reaction) {
                $userReaction = [
                    'type' => $reaction->getType(),
                    'emoji' => $reaction->getEmoji()
                ];
            }

            return new JsonResponse([
                'success' => true,
                'userReaction' => $userReaction
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false, 
                'error' => 'Erreur lors de la récupération de la réaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer la réaction d'un utilisateur pour une publication
     */
    #[Route('/remove/{publicationId}', name: 'reaction_remove', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function removeReaction(int $publicationId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'error' => 'Utilisateur non connecté'], 401);
        }

        try {
            $this->reactionRepository->removeUserReaction($publicationId, $user->getId());
            
            // Obtenir les nouveaux comptes
            $counts = $this->reactionRepository->getReactionsCountForPublication($publicationId);

            return new JsonResponse([
                'success' => true,
                'counts' => $counts,
                'message' => 'Réaction supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false, 
                'error' => 'Erreur lors de la suppression de la réaction: ' . $e->getMessage()
            ], 500);
        }
    }
}
