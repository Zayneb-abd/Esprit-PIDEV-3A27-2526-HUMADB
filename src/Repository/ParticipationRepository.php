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
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('u.nom', ':query'),
                    $qb->expr()->like('u.prenom', ':query'),
                    $qb->expr()->like('f.sujet', ':query'),
                    $qb->expr()->like('p.statut', ':query'),
                    $qb->expr()->like('p.resultat', ':query')
                )
            )
            ->setParameter('query', '%' . $query . '%');
        }

        $allowedSortFields = [
            'user' => 'u.nom',
            'formation' => 'f.sujet',
            'statut' => 'p.statut',
            'id' => 'p.id',
            'dateInscription' => 'p.dateInscription'
        ];
        if ($sortField && isset($allowedSortFields[$sortField])) {
            $qb->orderBy($allowedSortFields[$sortField], $sortOrder === 'DESC' ? 'DESC' : 'ASC');
        } else {
            $qb->orderBy('p.id', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }
}
