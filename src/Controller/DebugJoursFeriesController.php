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
        // Test 1: Vérifier la table
        $conn = $em->getConnection();
        $sql = "SELECT COUNT(*) as total FROM jours_feries";
        $count = $conn->executeQuery($sql)->fetchOne();
        
        // Test 2: Récupérer tous les jours fériés 2026
        $sql2 = "SELECT * FROM jours_feries WHERE annee = 2026 ORDER BY date";
        $all = $conn->executeQuery($sql2)->fetchAllAssociative();
        
        // Test 3: Tester le service
        $start = new \DateTime('2026-04-01');
        $end = new \DateTime('2026-04-30');
        $fromService = $jourFerieService->getJoursFeriesForPeriod($start, $end, 'TN');
        
        $html = '<h1>Debug Jours Fériés</h1>';
        $html .= '<h2>Total en base: ' . $count . '</h2>';
        
        $html .= '<h2>Tous les jours fériés 2026:</h2><ul>';
        foreach ($all as $j) {
            $html .= '<li>' . $j['date'] . ' - ' . $j['nom'] . '</li>';
        }
        $html .= '</ul>';
        
        $html .= '<h2>Via Service (Avril 2026):</h2><ul>';
        foreach ($fromService as $j) {
            $html .= '<li>' . $j->getDate()->format('Y-m-d') . ' - ' . $j->getNom() . '</li>';
        }
        $html .= '</ul>';
        
        return new Response($html);
    }
}
