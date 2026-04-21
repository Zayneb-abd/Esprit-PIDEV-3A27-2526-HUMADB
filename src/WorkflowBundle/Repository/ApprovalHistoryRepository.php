<?php

namespace App\WorkflowBundle\Repository;

use App\WorkflowBundle\Entity\ApprovalHistory;
use App\Entity\Conge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ApprovalHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApprovalHistory::class);
    }

    public function findOneByConge(Conge $conge): ?ApprovalHistory
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.conge = :conge')
            ->setParameter('conge', $conge)
            ->orderBy('h.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByConge(Conge $conge): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.conge = :conge')
            ->setParameter('conge', $conge)
            ->orderBy('h.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
