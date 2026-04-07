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

    public function searchAndSort(?string $query, ?string $sortField, string $sortOrder = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('f');

        if ($query) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('f.sujet', ':query'),
                    $qb->expr()->like('f.formateur', ':query'),
                    $qb->expr()->like('f.type', ':query'),
                    $qb->expr()->like('f.localisation', ':query')
                )
            )
            ->setParameter('query', '%' . $query . '%');
        }

        $allowedSortFields = ['sujet', 'formateur', 'type', 'date_debut', 'duree', 'localisation'];
        if ($sortField && in_array($sortField, $allowedSortFields, true)) {
            $qb->orderBy('f.' . $sortField, $sortOrder === 'DESC' ? 'DESC' : 'ASC');
        } else {
            $qb->orderBy('f.date_debut', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }
}
