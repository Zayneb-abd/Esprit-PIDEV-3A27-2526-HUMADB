<?php

namespace App\Repository;

use App\Entity\Formation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Formation>
 */
class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    //    /**
    //     * @return Formation[] Returns an array of Formation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    public function searchAndSort(?string $query, ?string $sortField, ?string $sortOrder): array
    {
        $qb = $this->createQueryBuilder('f');

        if ($query) {
            $qb->andWhere('f.sujet LIKE :query OR f.formateur LIKE :query OR f.type LIKE :query OR f.localisation LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        if ($sortField) {
            $qb->orderBy('f.' . $sortField, $sortOrder ?: 'ASC');
        } else {
            $qb->orderBy('f.id', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }
}
