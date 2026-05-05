<?php

namespace App\Repository;

use App\Entity\Conge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conge>
 */
class CongeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conge::class);
    }

    //    /**
    //     * @return Conge[] Returns an array of Conge objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Conge
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function searchAndSort(?string $query, ?string $sortField, ?string $sortOrder): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.absence', 'a')
            ->leftJoin('a.user', 'u');

        if ($query) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('c.commentaire_validation', ':query'),
                    $qb->expr()->like('c.statut', ':query'),
                    $qb->expr()->like('a.type_absence', ':query'),
                    $qb->expr()->like('u.nom', ':query'),
                    $qb->expr()->like('u.prenom', ':query')
                )
            )
            ->setParameter('query', '%' . $query . '%');
        }

        $allowedSortFields = ['date_demande', 'statut', 'commentaire_validation'];
        if ($sortField && in_array($sortField, $allowedSortFields, true)) {
            $qb->orderBy('c.' . $sortField, $sortOrder === 'DESC' ? 'DESC' : 'ASC');
        } else {
            $qb->orderBy('c.date_demande', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find conges by date range and user IDs
     * @param array<int> $userIds
     * @return array<Conge>
     */
    public function findByDateRangeAndUsers(\DateTime $start, \DateTime $end, array $userIds): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user IN (:userIds)')
            ->andWhere('c.date_debut <= :end')
            ->andWhere('c.date_fin >= :start')
            ->setParameter('userIds', $userIds)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }
}
