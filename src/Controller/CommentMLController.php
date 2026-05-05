<?php

namespace App\Controller;

use App\Service\MLPredictionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/commentaire/ml')]
class CommentMLController extends AbstractController
{
    private MLPredictionService $mlService;

    public function __construct(MLPredictionService $mlService)
    {
        $this->mlService = $mlService;
    }

    #[Route('/predict-engagement', name: 'comment_predict_engagement', methods: ['POST'])]
    public function predictEngagement(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $content = $data['content'] ?? '';

            if (empty($content)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Contenu vide'
                ], 400);
            }

            // Test simple sans ML pour debug
            $length = strlen($content);
            $hasQuestion = strpos($content, '?') !== false;
            
            $score = 5.0;
            if ($hasQuestion) $score += 2.0;
            if ($length >= 20 && $length <= 200) $score += 1.5;
            
            return new JsonResponse([
                'success' => true,
                'data' => [
                    'engagement_score' => min(10, $score),
                    'estimated_reactions' => (int)($score * 2),
                    'estimated_responses' => (int)($score * 0.8),
                    'recommendations' => $hasQuestion ? [] : ['Ajoutez une question (+30% engagement)'],
                    'method' => 'php_test_mode'
                ]
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/demo', name: 'comment_ml_demo')]
    public function demo(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('commentaire/ml_demo.html.twig');
    }
}
