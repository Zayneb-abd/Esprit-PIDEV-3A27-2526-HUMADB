<?php

namespace App\Repository;

use App\Entity\ChatMessage;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChatMessage>
 */
class ChatMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatMessage::class);
    }

    public function save(ChatMessage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ChatMessage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Get last N messages for a session
     */
    public function findLastMessagesBySession(string $sessionId, int $limit = 10): array
    {
        return $this->createQueryBuilder('cm')
            ->where('cm.sessionId = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('cm.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all messages for a session ordered by creation date
     */
    public function findMessagesBySession(string $sessionId): array
    {
        return $this->createQueryBuilder('cm')
            ->where('cm.sessionId = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('cm.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all unique sessions for a user
     */
    public function findUserSessions(User $user): array
    {
        return $this->createQueryBuilder('cm')
            ->select('cm.sessionId', 'MIN(cm.createdAt) as firstMessageAt', 'COUNT(cm.id) as messageCount')
            ->where('cm.user = :user')
            ->setParameter('user', $user)
            ->groupBy('cm.sessionId')
            ->orderBy('firstMessageAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get first message of each session for preview
     */
    public function findSessionPreviews(User $user): array
    {
        $sessions = $this->findUserSessions($user);
        $previews = [];

        foreach ($sessions as $session) {
            $firstMessage = $this->createQueryBuilder('cm')
                ->where('cm.sessionId = :sessionId')
                ->setParameter('sessionId', $session['sessionId'])
                ->orderBy('cm.createdAt', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($firstMessage) {
                $previews[] = [
                    'sessionId' => $session['sessionId'],
                    'firstMessageAt' => $session['firstMessageAt'],
                    'messageCount' => $session['messageCount'],
                    'firstMessage' => $firstMessage->getContent()
                ];
            }
        }

        return $previews;
    }

    /**
     * Delete all messages for a session
     */
    public function clearSession(string $sessionId): int
    {
        return $this->createQueryBuilder('cm')
            ->delete()
            ->where('cm.sessionId = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->getQuery()
            ->execute();
    }

    /**
     * Count messages per user in last 24 hours (for rate limiting)
     */
    public function countUserMessagesLast24Hours(User $user): int
    {
        $yesterday = new \DateTimeImmutable('24 hours ago');

        return $this->createQueryBuilder('cm')
            ->select('COUNT(cm.id)')
            ->where('cm.user = :user')
            ->andWhere('cm.createdAt >= :since')
            ->setParameter('user', $user)
            ->setParameter('since', $yesterday)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count messages per user in last minute (for rate limiting)
     */
    public function countUserMessagesLastMinute(User $user): int
    {
        $oneMinuteAgo = new \DateTimeImmutable('1 minute ago');

        return $this->createQueryBuilder('cm')
            ->select('COUNT(cm.id)')
            ->where('cm.user = :user')
            ->andWhere('cm.createdAt >= :since')
            ->setParameter('user', $user)
            ->setParameter('since', $oneMinuteAgo)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
