<?php

namespace App\Controller;

use App\Service\MLPredictionService;
use App\Repository\CongeRepository;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur pour les fonctionnalités de Machine Learning
 * Prédiction des congés, détection d'anomalies, suggestions
 */
#[Route('/ml')]
class MLPredictionController extends AbstractController
{
    private MLPredictionService $mlService;
    private CongeRepository $congeRepository;
    private Security $security;
    
    public function __construct(
        MLPredictionService $mlService,
        CongeRepository $congeRepository,
        Security $security
    ) {
        $this->mlService = $mlService;
        $this->congeRepository = $congeRepository;
        $this->security = $security;
    }
    
    /**
     * Dashboard ML pour l'employé
     * Affiche les prédictions personnelles
     */
    #[Route('/dashboard', name: 'ml_dashboard')]
    public function dashboard(): Response
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }
        
        // Prédiction du prochain congé
        $prediction = $this->mlService->predictCongeProbability($user->getId());
        
        // Suggestion de période pour 5 jours
        $suggestion = $this->mlService->suggestBestPeriod($user->getId(), 5);
        
        return $this->render('ml/dashboard.html.twig', [
            'user' => $user,
            'prediction' => $prediction,
            'suggestion' => $suggestion,
            'historique_conges' => $this->congeRepository->findBy(['user' => $user], ['dateDemande' => 'DESC'], 10)
        ]);
    }
    
    /**
     * API JSON pour la prédiction (AJAX)
     */
    #[Route('/api/predict/{userId}', name: 'ml_api_predict', methods: ['GET'])]
    public function apiPredict(int $userId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');
        
        $result = $this->mlService->predictCongeProbability($userId);
        
        return $this->json($result);
    }
    
    /**
     * Analyse d'anomalies pour le manager
     */
    #[Route('/manager/anomalies', name: 'ml_manager_anomalies')]
    public function managerAnomalies(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');
        
        // TODO: Analyser tous les employés de l'équipe
        
        return $this->render('ml/manager_anomalies.html.twig', [
            'title' => 'Détection d\'anomalies - ML'
        ]);
    }
}
