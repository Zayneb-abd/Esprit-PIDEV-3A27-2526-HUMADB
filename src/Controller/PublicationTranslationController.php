<?php

namespace App\Controller;

use App\Entity\Publication;
use App\Service\AIService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/publications')]
#[IsGranted('ROLE_USER')]
class PublicationTranslationController extends AbstractController
{
    private const SUPPORTED_LOCALES = ['fr', 'en', 'ar', 'ru'];

    #[Route('/{id}/translate/{locale}', name: 'api_publication_translate', methods: ['GET'], requirements: ['id' => '\d+', 'locale' => 'fr|en|ar|ru'])]
    public function translate(Publication $publication, string $locale, AIService $aiService): JsonResponse
    {
        $content = trim(html_entity_decode(strip_tags((string) $publication->getContenu())));

        if ($content === '') {
            return new JsonResponse([
                'success' => false,
                'error' => 'La publication est vide.',
            ], 400);
        }

        try {
            $translated = $aiService->translateText($content, $locale);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Impossible de traduire la publication: ' . $exception->getMessage(),
            ], 500);
        }

        return new JsonResponse([
            'success' => true,
            'publication_id' => $publication->getId(),
            'source_locale' => 'fr',
            'target_locale' => $locale,
            'original_content' => $content,
            'translated_content' => $translated,
            'supported_locales' => self::SUPPORTED_LOCALES,
        ]);
    }
}
