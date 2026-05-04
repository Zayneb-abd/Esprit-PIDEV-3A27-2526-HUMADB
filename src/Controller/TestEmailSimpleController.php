<?php

namespace App\Controller;

use Symfony\Component\Mime\Email;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/test')]
class TestEmailSimpleController extends AbstractController
{
    #[Route('/simple-email', name: 'test_simple_email')]
    public function testSimpleEmail(MailerInterface $mailer): Response
    {
        try {
            // Email simple sans template
            $email = (new Email())
                ->from('noreply@huma.tn')
                ->to('test@example.com')
                ->subject('Test Simple Email')
                ->html('<h1>Test</h1><p>Ceci est un test d\'email.</p>');

            $mailer->send($email);

            return new Response('✅ Email simple envoyé! Check Mailtrap.');
        } catch (\Exception $e) {
            return new Response('❌ Erreur: ' . $e->getMessage() . '<br><br>Détails: ' . $e->getTraceAsString());
        }
    }
}
