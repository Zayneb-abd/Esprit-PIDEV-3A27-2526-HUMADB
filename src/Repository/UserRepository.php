<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setMdp($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Search users by name or email, paginated.
     *
     * @return User[]
     */
    public function searchPaginated(string $search = '', ?string $role = null, int $page = 1, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC');

        if ($search !== '') {
            $qb->andWhere('u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($role !== null && $role !== '') {
            $qb->andWhere('u.role = :role')
                ->setParameter('role', $role);
        }

        return $qb->setMaxResults($limit)
            ->setFirstResult(($page - 1) * $limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count users matching search.
     */
    public function countSearch(string $search = '', ?string $role = null): int
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)');

        if ($search !== '') {
            $qb->andWhere('u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($role !== null && $role !== '') {
            $qb->andWhere('u.role = :role')
                ->setParameter('role', $role);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Count users by role.
     *
     * @return array<string, int>
     */
    public function countByRole(): array
    {
        /** @var \App\DTO\CountByFieldDTO[] $results */
        $results = $this->createQueryBuilder('u')
            ->select('NEW App\DTO\CountByFieldDTO(COALESCE(u.role, \'UNKNOWN\'), COUNT(u.id))')
            ->groupBy('u.role')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $dto) {
            $counts[$dto->key] = $dto->count;
        }

        return $counts;
    }

    /**
     * Get recently registered users.
     *
     * @return User[]
     */
    public function findRecentUsers(int $limit = 5): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findOneByResetToken(string $token): ?User
    {
        return $this->findOneBy(['reset_token' => $token]);
    }

    /**
     * @return User[]
     */
    public function findForExport(string $search = '', ?string $role = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC');

        if ($search !== '') {
            $qb->andWhere('u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($role !== null && $role !== '') {
            $qb->andWhere('u.role = :role')
                ->setParameter('role', $role);
        }

        return $qb->getQuery()->getResult();
    }
}
