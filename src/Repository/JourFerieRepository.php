<?php

namespace App\Repository;

use App\Entity\JourFerie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JourFerie>
 */
class JourFerieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JourFerie::class);
    }

    public function findByAnnee(int $annee, string $pays = 'TN'): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.annee = :annee')
            ->andWhere('j.pays = :pays')
            ->setParameter('annee', $annee)
            ->setParameter('pays', $pays)
            ->orderBy('j.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByDate(\DateTime $date, string $pays = 'TN'): ?JourFerie
    {
        return $this->createQueryBuilder('j')
            ->where('j.date = :date')
            ->andWhere('j.pays = :pays')
            ->setParameter('date', $date)
            ->setParameter('pays', $pays)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
