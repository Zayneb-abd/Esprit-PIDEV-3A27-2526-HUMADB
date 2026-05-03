<?php

namespace App\Repository;

use App\Entity\Participation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Participation>
 */
class ParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participation::class);
    }

    //    /**
    //     * @return Participation[] Returns an array of Participation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    public function searchAndSort(?string $query, ?string $sortField, ?string $sortOrder, ?int $userId = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->join('p.formation', 'f');

        if ($userId) {
            $qb->andWhere('u.id = :userId')
               ->setParameter('userId', $userId);
        }

        if ($query) {
            $qb->andWhere('u.nom LIKE :query OR u.prenom LIKE :query OR f.sujet LIKE :query OR p.statut LIKE :query OR p.resultat LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        if ($sortField) {
            if ($sortField === 'user') {
                $qb->orderBy('u.nom', $sortOrder ?: 'ASC');
            } elseif ($sortField === 'formation') {
                $qb->orderBy('f.sujet', $sortOrder ?: 'ASC');
            } elseif ($sortField === 'statut') {
                $qb->orderBy('p.statut', $sortOrder ?: 'ASC');
            } else {
                $qb->orderBy('p.' . $sortField, $sortOrder ?: 'ASC');
            }
        } else {
            $qb->orderBy('p.id', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }
}
