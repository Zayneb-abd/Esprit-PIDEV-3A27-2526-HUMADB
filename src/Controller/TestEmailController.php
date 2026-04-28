<?php

namespace App\Controller;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/test')]
class TestEmailController extends AbstractController
{
    #[Route('/email', name: 'test_email')]
    public function testEmail(MailerInterface $mailer): Response
    {
        try {
            $email = (new TemplatedEmail())
                ->from('noreply@huma.tn')
                ->to('test@example.com')
                ->subject('Test Email')
                ->htmlTemplate('emails/conge_submitted.html.twig')
                ->context([
                    'user' => $this->getUser(),
                    'conge' => null,
                    'absence' => null,
                ]);

            $mailer->send($email);

            return new Response('✅ Email envoyé avec succès! Vérifie Mailtrap.');
        } catch (\Exception $e) {
            return new Response('❌ Erreur: ' . $e->getMessage());
        }
    }
}
