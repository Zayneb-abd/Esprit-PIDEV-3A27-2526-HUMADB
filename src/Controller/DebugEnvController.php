<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/debug')]
class DebugEnvController extends AbstractController
{
    #[Route('/env', name: 'debug_env')]
    public function debugEnv(): Response
    {
        $mailerDsn = $_ENV['MAILER_DSN'] ?? getenv('MAILER_DSN') ?? 'NOT FOUND';
        
        return new Response('
            <h1>Debug MAILER_DSN</h1>
            <p><strong>MAILER_DSN:</strong> ' . htmlspecialchars($mailerDsn) . '</p>
            <p><strong>All ENV vars:</strong></p>
            <pre>' . print_r($_ENV, true) . '</pre>
        ');
    }
}
