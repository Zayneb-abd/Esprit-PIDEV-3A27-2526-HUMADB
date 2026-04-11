<?php

namespace App\Repository;

use App\Entity\Absence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Absence>
 */
class AbsenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Absence::class);
    }

    //    /**
    //     * @return Absence[] Returns an array of Absence objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Absence
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function searchAndSort(?string $query, ?string $sortField, ?string $sortOrder): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u');

        if ($query) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('a.type_absence', ':query'),
                    $qb->expr()->like('a.statut', ':query'),
                    $qb->expr()->like('u.nom', ':query'),
                    $qb->expr()->like('u.prenom', ':query')
                )
            )
            ->setParameter('query', '%' . $query . '%');
        }

        $allowedSortFields = ['date_debut', 'date_fin', 'type_absence', 'statut'];
        if ($sortField && in_array($sortField, $allowedSortFields, true)) {
            $qb->orderBy('a.' . $sortField, $sortOrder === 'DESC' ? 'DESC' : 'ASC');
        } else {
            $qb->orderBy('a.date_debut', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }
}
