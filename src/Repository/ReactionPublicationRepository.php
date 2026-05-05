<?php

namespace App\Repository;

use App\Entity\ReactionPublication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReactionPublication>
 */
class ReactionPublicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReactionPublication::class);
    }

    public function save(ReactionPublication $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ReactionPublication $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Compter les likes et dislikes pour une publication
     * @return array<string, int>
     */
    public function getReactionsCountForPublication(int $publicationId): array
    {
        $qb = $this->createQueryBuilder('rp')
            ->select('LOWER(rp.type) AS reactionType, COUNT(rp.id) AS count')
            ->where('rp.publication = :publicationId')
            ->groupBy('reactionType')
            ->setParameter('publicationId', $publicationId);

        $results = $qb->getQuery()->getResult();
        
        $counts = [
            'like' => 0,
            'dislike' => 0
        ];
        
        foreach ($results as $result) {
            $type = strtolower((string) ($result['reactionType'] ?? $result['type'] ?? ''));
            if (isset($counts[$type])) {
                $counts[$type] = (int) $result['count'];
            }
        }
        
        return $counts;
    }

    /**
     * Obtenir la réaction d'un utilisateur pour une publication
     */
    public function getUserReactionForPublication(int $publicationId, int $userId): ?ReactionPublication
    {
        return $this->createQueryBuilder('rp')
            ->where('rp.publication = :publicationId')
            ->andWhere('rp.user = :userId')
            ->setParameter('publicationId', $publicationId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Ajouter ou mettre à jour une réaction
     */
    public function addOrUpdateReaction(int $publicationId, int $userId, string $type): ?ReactionPublication
    {
        $type = strtolower(trim($type));

        // Vérifier si l'utilisateur a déjà réagi
        $existingReaction = $this->getUserReactionForPublication($publicationId, $userId);
        
        if ($existingReaction) {
            // Si même type, supprimer la réaction
            if ($existingReaction->getType() === $type) {
                $this->remove($existingReaction, true);
                return null;
            }
            // Sinon, mettre à jour le type
            $existingReaction->setType($type);
            $this->save($existingReaction, true);
            return $existingReaction;
        }
        
        // Créer une nouvelle réaction
        $reaction = new ReactionPublication();
        $reaction->setType($type);
        
        // Récupérer la publication
        $publication = $this->getEntityManager()->getReference('App\Entity\Publication', $publicationId);
        $reaction->setPublication($publication);
        
        // Récupérer l'utilisateur
        $user = $this->getEntityManager()->getReference('App\Entity\User', $userId);
        $reaction->setUser($user);
        
        $this->save($reaction, true);
        return $reaction;
    }

    /**
     * Supprimer la réaction d'un utilisateur pour une publication
     */
    public function removeUserReaction(int $publicationId, int $userId): void
    {
        $reaction = $this->getUserReactionForPublication($publicationId, $userId);
        if ($reaction) {
            $this->remove($reaction, true);
        }
    }

    /**
     * Obtenir les publications les plus aimées
     * @return array<int, array<string, mixed>>
     */
    public function getMostLikedPublications(int $limit = 10): array
    {
        return $this->createQueryBuilder('rp')
            ->select('p.id, p.contenu, COUNT(rp.id) as likeCount')
            ->leftJoin('rp.publication', 'p')
            ->where('rp.type = :type')
            ->groupBy('p.id')
            ->orderBy('likeCount', 'DESC')
            ->setParameter('type', ReactionPublication::TYPE_LIKE)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
