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
    public function searchForAdmin(string $query = '', ?string $priorityFilter = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->addSelect('(CASE
                WHEN f.priority = \'urgente\' THEN 0
                WHEN f.priority = \'haute\' THEN 1
                WHEN f.priority = \'normal\' THEN 2
                WHEN f.priority = \'bas\' THEN 3
                ELSE 2 END) AS HIDDEN prioOrd')
            ->orderBy('prioOrd', 'ASC')
            ->addOrderBy('f.date_envoi', 'DESC')
            ->addOrderBy('f.id', 'DESC');

        if ($priorityFilter !== null && $priorityFilter !== '') {
            $qb->andWhere('f.priority = :prio')
                ->setParameter('prio', $priorityFilter);
        }

        $query = trim($query);
        if ($query !== '') {
            $qb->andWhere('
                LOWER(f.contenu) LIKE :q
                OR LOWER(f.category) LIKE :q
                OR LOWER(f.status) LIKE :q
                OR LOWER(f.priority) LIKE :q
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
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        /** @var \App\DTO\CountByFieldDTO[] $rows */
        $rows = $this->createQueryBuilder('f')
            ->select('NEW App\DTO\CountByFieldDTO(f.status, COUNT(f.id))')
            ->groupBy('f.status')
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($rows as $dto) {
            $key = $dto->key;
            $out[$key] = $dto->count;
        }

        return $out;
    }

    /**
     * @return array<string, int>
     */
    public function countByCategory(): array
    {
        /** @var \App\DTO\CountByFieldDTO[] $rows */
        $rows = $this->createQueryBuilder('f')
            ->select('NEW App\DTO\CountByFieldDTO(COALESCE(f.category, \'-\'), COUNT(f.id))')
            ->groupBy('f.category')
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($rows as $dto) {
            $out[$dto->key] = $dto->count;
        }

        return $out;
    }

    /**
     * @return array<string, int>
     */
    public function countByPriority(): array
    {
        /** @var \App\DTO\CountByFieldDTO[] $rows */
        $rows = $this->createQueryBuilder('f')
            ->select('NEW App\DTO\CountByFieldDTO(COALESCE(f.priority, \'normal\'), COUNT(f.id))')
            ->groupBy('f.priority')
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($rows as $dto) {
            $out[$dto->key] = $dto->count;
        }

        return $out;
    }

    /**
     * @return array{labels: string[], data: int[]}
     */
    public function countByDayLastDays(int $days = 7): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $since = (new \DateTimeImmutable("-{$days} days"))->setTime(0, 0, 0)->format('Y-m-d');

        $sql = 'SELECT DATE(date_envoi) AS d, COUNT(*) AS c FROM feedback WHERE date_envoi >= :since GROUP BY d ORDER BY d ASC';
        $stmt = $conn->executeQuery($sql, ['since' => $since]);
        $rows = $stmt->fetchAllAssociative();

        $byDay = [];
        foreach ($rows as $row) {
            $byDay[(string) $row['d']] = (int) $row['c'];
        }

        $labels = [];
        $data = [];
        for ($i = $days - 1; $i >= 0; --$i) {
            $day = (new \DateTimeImmutable("-{$i} days"))->format('Y-m-d');
            $labels[] = (new \DateTimeImmutable($day))->format('d/m');
            $data[] = $byDay[$day] ?? 0;
        }

        return ['labels' => $labels, 'data' => $data];
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
