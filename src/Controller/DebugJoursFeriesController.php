<?php

namespace App\Controller;

use App\Service\JourFerieService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/debug')]
class DebugJoursFeriesController extends AbstractController
{
    #[Route('/jours-feries', name: 'debug_jours_feries')]
    public function debugJoursFeries(
        JourFerieService $jourFerieService,
        EntityManagerInterface $em
    ): Response {
        // Logique de debug pour les jours fériés
        $joursFeries = $jourFerieService->fetchJoursFeriesFromAPI((int)date('Y'));
        
        return $this->render('debug/jours_feries.html.twig', [
            'jours_feries' => $joursFeries,
        ]);
    }
}
