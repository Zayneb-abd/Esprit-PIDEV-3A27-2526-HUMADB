<?php

namespace App\Controller;

use App\Service\ChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/chatbot')]
#[IsGranted('ROLE_ADMIN')]
class ChatbotController extends AbstractController
{
    private ChatbotService $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * Génère une suggestion de publication
     */
    #[Route('/suggest', name: 'chatbot_suggest', methods: ['POST'])]
    public function suggestPublication(Request $request): JsonResponse
    {
        $subject = (string) $request->request->get('subject');
        $context = (string) $request->request->get('context', '');

        if (empty($subject)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Le sujet est obligatoire'
            ], 400);
        }

        $result = $this->chatbotService->generatePublicationSuggestion($subject, $context);

        return new JsonResponse($result);
    }
}
