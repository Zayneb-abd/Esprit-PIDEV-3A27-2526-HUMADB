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

    public function findByApprover($approver): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.approver = :approver')
            ->setParameter('approver', $approver)
            ->orderBy('h.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('h')
            ->orderBy('h.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByEmployee($employee): array
    {
        return $this->createQueryBuilder('h')
            ->leftJoin('h.conge', 'c')
            ->leftJoin('h.absence', 'a')
            ->leftJoin('c.user', 'cu')
            ->leftJoin('a.user', 'au')
            ->andWhere('cu = :employee OR au = :employee')
            ->setParameter('employee', $employee)
            ->orderBy('h.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
