<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    /**
     * Cette méthode est appelée depuis Twig avec render(controller(...))
     * Elle rend UNIQUEMENT la partie intérieure de la cloche (le `<li>` de la cloche et le dropdown).
     */
    public function renderDropdown(string $style, NotificationRepository $notificationRepository): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new Response(''); // Ne rien afficher si non connecté
        }

        $notifications = $notificationRepository->findUnreadByUser($user->getId());
        $unreadCount = count($notifications);
        // On limite l'affichage aux 5 dernières pour le dropdown
        $recentNotifications = array_slice($notifications, 0, 5);

        return $this->render('notification/_dropdown_' . $style . '.html.twig', [
            'notifications' => $recentNotifications,
            'unread_count' => $unreadCount,
        ]);
    }

    #[Route('/notification/{id}/read', name: 'notification_mark_read')]
    public function markAsRead(Notification $notification, EntityManagerInterface $entityManager): Response
    {
        // Optionnel : vérifier si c'est bien l'utilisateur courant qui lit sa notification
        $user = $this->getUser();
        if ($user && $notification->getUser() === $user) {
            $notification->setIsRead(true);
            $entityManager->flush();
        }

        $link = $notification->getReferenceLink();
        if ($link) {
            return $this->redirect($link);
        }

        // Si aucun lien n'a été défini, on renvoie vers l'accueil ou le dashboard
        return $this->redirectToRoute('app_login'); 
    }
}
