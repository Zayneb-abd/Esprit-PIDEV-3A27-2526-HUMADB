<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository
    ) {}

    public function sendToUser(User $user, string $title, string $message, ?string $link = null): void
    {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setTitle($title);
        $notification->setMessage($message);
        $notification->setReferenceLink($link);
        // is_read and created_at are handled by the entity constructor

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    public function sendToAdmins(string $title, string $message, ?string $link = null): void
    {
        // On récupère tous les utilisateurs ayant le rôle ADMIN_RH
        // Le rôle dans la base de données est souvent stocké comme 'role' string
        $admins = $this->userRepository->createQueryBuilder('u')
            ->where("u.role = 'ADMIN_RH'")
            ->orWhere("u.role LIKE '%ADMIN%'")
            ->getQuery()
            ->getResult();

        foreach ($admins as $admin) {
            $notification = new Notification();
            $notification->setUser($admin);
            $notification->setTitle($title);
            $notification->setMessage($message);
            $notification->setReferenceLink($link);
            
            $this->entityManager->persist($notification);
        }

        $this->entityManager->flush();
    }
}
