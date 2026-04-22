<?php

namespace App\Service;

use App\Entity\Conge;
use App\Entity\Absence;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;

class NotificationService
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private string $senderEmail;
    private string $senderName;

    public function __construct(
        MailerInterface $mailer,
        LoggerInterface $logger,
        string $senderEmail = 'noreply@huma.tn',
        string $senderName = 'HUMA RH System'
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
    }

    public function notifyEmployeeRequestSubmitted(Conge $conge): void
    {
        $user = $conge->getUser();
        if (!$user || !$user->getEmail()) {
            return;
        }

        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to($user->getEmail())
                ->subject('Votre demande de congé a été soumise')
                ->htmlTemplate('emails/conge_submitted.html.twig')
                ->context([
                    'user' => $user,
                    'conge' => $conge,
                    'absence' => $conge->getAbsence(),
                ]);

            $this->mailer->send($email);
            $this->logger->info('Email soumission envoyé à ' . $user->getEmail());
        } catch (\Exception $e) {
            $this->logger->error('Erreur email soumission: ' . $e->getMessage());
        }
    }

    public function notifyManagerNewRequest(Conge $conge, User $manager): void
    {
        if (!$manager || !$manager->getEmail()) {
            return;
        }

        $user = $conge->getUser();

        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to($manager->getEmail())
                ->subject('Nouvelle demande de congé de ' . $user->getPrenom() . ' ' . $user->getNom())
                ->htmlTemplate('emails/manager_new_request.html.twig')
                ->context([
                    'manager' => $manager,
                    'employee' => $user,
                    'conge' => $conge,
                    'absence' => $conge->getAbsence(),
                ]);

            $this->mailer->send($email);
            $this->logger->info('Email manager envoyé à ' . $manager->getEmail());
        } catch (\Exception $e) {
            $this->logger->error('Erreur email manager: ' . $e->getMessage());
        }
    }

    public function notifyEmployeeRequestApproved(Conge $conge, ?string $comment = null): void
    {
        $user = $conge->getUser();
        if (!$user || !$user->getEmail()) {
            return;
        }

        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to($user->getEmail())
                ->subject('Votre demande de congé a été APPROUVÉE')
                ->htmlTemplate('emails/conge_approved.html.twig')
                ->context([
                    'user' => $user,
                    'conge' => $conge,
                    'absence' => $conge->getAbsence(),
                    'comment' => $comment,
                ]);

            $this->mailer->send($email);
            $this->logger->info('Email approbation envoyé à ' . $user->getEmail());
        } catch (\Exception $e) {
            $this->logger->error('Erreur email approbation: ' . $e->getMessage());
        }
    }

    public function notifyEmployeeRequestRejected(Conge $conge, string $reason): void
    {
        $user = $conge->getUser();
        if (!$user || !$user->getEmail()) {
            return;
        }

        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to($user->getEmail())
                ->subject('Votre demande de congé a été REFUSÉE')
                ->htmlTemplate('emails/conge_rejected.html.twig')
                ->context([
                    'user' => $user,
                    'conge' => $conge,
                    'absence' => $conge->getAbsence(),
                    'reason' => $reason,
                ]);

            $this->mailer->send($email);
            $this->logger->info('Email refus envoyé à ' . $user->getEmail());
        } catch (\Exception $e) {
            $this->logger->error('Erreur email refus: ' . $e->getMessage());
        }
    }
}
