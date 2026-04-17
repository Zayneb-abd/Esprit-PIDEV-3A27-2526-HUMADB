<?php

namespace App\Repository;

use App\Entity\Feedback;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Feedback>
 */
class FeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feedback::class);
    }

    /**
     * @return Feedback[]
     */
    public function searchForAdmin(string $query = ''): array
    {
        $qb = $this->createQueryBuilder('f')
            ->orderBy('f.date_envoi', 'DESC')
            ->addOrderBy('f.id', 'DESC');

        $query = trim($query);
        if ($query !== '') {
            $qb->andWhere('
                LOWER(f.contenu) LIKE :q
                OR LOWER(f.category) LIKE :q
                OR LOWER(f.status) LIKE :q
            ')
            ->setParameter('q', '%' . mb_strtolower($query) . '%');

            // If the query is numeric, also allow exact match on employee id.
            if (ctype_digit($query)) {
                $qb->orWhere('f.employe_id = :employeeId')
                    ->setParameter('employeeId', (int) $query);
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Feedback[]
     */
    public function searchForEmployee(int $employeeId, string $query = ''): array
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.employe_id = :employeeId')
            ->setParameter('employeeId', $employeeId)
            ->orderBy('f.date_envoi', 'DESC')
            ->addOrderBy('f.id', 'DESC');

        $query = trim($query);
        if ($query !== '') {
            $qb->andWhere('
                LOWER(f.contenu) LIKE :q
                OR LOWER(f.category) LIKE :q
                OR LOWER(f.status) LIKE :q
            ')
            ->setParameter('q', '%' . mb_strtolower($query) . '%');
        }

        return $qb->getQuery()->getResult();
    }

}
