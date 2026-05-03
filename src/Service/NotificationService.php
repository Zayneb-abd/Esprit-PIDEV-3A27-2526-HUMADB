<?php

namespace App\Service;

use App\Entity\Absence;
use App\Entity\Conge;
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

    public function notifyEmployeeRequestSubmitted(Conge $conge): void
    {
        $employee = $conge->getUser();
        if (!$employee instanceof User) {
            return;
        }

        $this->sendToUser(
            $employee,
            'Demande de congé envoyée',
            'Votre demande de congé a bien été soumise et est en attente de validation.',
            null
        );
    }

    public function notifyManagerNewRequest(Conge $conge, User $manager): void
    {
        if (!$manager instanceof User) {
            return;
        }

        $employee = $conge->getUser();
        $employeeName = $employee instanceof User
            ? trim($employee->getPrenom().' '.$employee->getNom())
            : 'Un employé';

        $this->sendToUser(
            $manager,
            'Nouvelle demande de congé',
            sprintf('%s a soumis une nouvelle demande de congé.', $employeeName),
            null
        );
    }

    public function notifyEmployeeRequestApproved(Conge $conge, string $comment = ''): void
    {
        $employee = $conge->getUser();
        if (!$employee instanceof User) {
            return;
        }

        $message = 'Votre demande de congé a été approuvée.';
        if (trim($comment) !== '') {
            $message .= ' Commentaire: '.$comment;
        }

        $this->sendToUser($employee, 'Demande de congé approuvée', $message, null);
    }

    public function notifyEmployeeRequestRejected(Absence $absence, string $comment = ''): void
    {
        $employee = $absence->getUser();
        if (!$employee instanceof User) {
            return;
        }

        $message = 'Votre demande de congé a été refusée.';
        if (trim($comment) !== '') {
            $message .= ' Commentaire: '.$comment;
        }

        $this->sendToUser($employee, 'Demande de congé refusée', $message, null);
    }
}
