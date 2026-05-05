<?php

namespace App\Controller;

use App\Service\MLPredictionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/publication/ml')]
class PublicationMLController extends AbstractController
{
    private MLPredictionService $mlService;

    public function __construct(MLPredictionService $mlService)
    {
        $this->mlService = $mlService;
    }

    #[Route('/predict-engagement', name: 'publication_predict_engagement', methods: ['POST'])]
    public function predictEngagement(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $content = $data['content'] ?? '';
        $type = $data['type'] ?? '';

        if (empty($content)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Contenu vide'
            ], 400);
        }

        $prediction = $this->mlService->predictPublicationEngagement($content, $type);

        return new JsonResponse($prediction);
    }

    #[Route('/demo', name: 'publication_ml_demo')]
    public function demo(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('publication/ml_demo.html.twig');
    }
}
