<?php

namespace App\Repository;

use App\Entity\Publication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Publication>
 */
class PublicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Publication::class);
    }

    /**
     * @return Publication[]
     */
    public function searchByKeyword(string $keyword): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->orderBy('p.date_publication', 'DESC')
            ->addOrderBy('p.id', 'DESC');

        $keyword = trim($keyword);
        if ($keyword !== '') {
            $qb->andWhere('
                LOWER(p.contenu) LIKE :q
                OR LOWER(p.type) LIKE :q
                OR LOWER(u.nom) LIKE :q
                OR LOWER(u.prenom) LIKE :q
                OR LOWER(u.email) LIKE :q
            ')
            ->setParameter('q', '%' . mb_strtolower($keyword) . '%');
        }

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($qb->getQuery());
        $paginator->setUseOutputWalkers(false);
        return iterator_to_array($paginator);
    }

}
