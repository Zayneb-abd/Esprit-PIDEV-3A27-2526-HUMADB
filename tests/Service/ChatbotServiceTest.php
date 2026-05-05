<?php

namespace App\Tests\Service;

use App\Service\ChatbotService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ChatbotServiceTest extends TestCase
{
    private ChatbotService $chatbotService;
    private $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->chatbotService = new ChatbotService($this->httpClient, 'test-api-key');
    }

    public function testGeneratePublicationSuggestionWithoutApiKey(): void
    {
        $service = new ChatbotService($this->httpClient, '');
        $result = $service->generatePublicationSuggestion('développement web', '');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Clé Groq API non configurée', $result['error']);
        $this->assertNull($result['suggestion']);
    }

    public function testGeneratePublicationSuggestionWithInvalidSubject(): void
    {
        $result = $this->chatbotService->generatePublicationSuggestion('cuisine et recettes', '');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('doit être lié', $result['error']);
        $this->assertNull($result['suggestion']);
    }

    public function testGeneratePublicationSuggestionWithValidSubject(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('toArray')->willReturn([
            'choices' => [
                [
                    'message' => [
                        'content' => 'Titre: Développement Web Moderne\nContenu: Le développement web est essentiel pour toute entreprise moderne.\nHashtags: #Web #Dev #Tech'
                    ]
                ]
            ],
            'usage' => ['total_tokens' => 150]
        ]);

        $this->httpClient->method('request')->willReturn($mockResponse);

        $result = $this->chatbotService->generatePublicationSuggestion('développement web', '');
        
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['suggestion']);
        $this->assertEquals(150, $result['tokens_used']);
        $this->assertEquals('Groq', $result['provider']);
    }

    public function testGeneratePublicationSuggestionWithApiError(): void
    {
        $this->httpClient->method('request')->willThrowException(new \Exception('Connection failed'));

        $result = $this->chatbotService->generatePublicationSuggestion('développement', '');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Erreur de connexion', $result['error']);
    }

    public function testGeneratePublicationSuggestionWithInvalidApiResponse(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('toArray')->willReturn(['invalid' => 'response']);

        $this->httpClient->method('request')->willReturn($mockResponse);

        $result = $this->chatbotService->generatePublicationSuggestion('développement web', '');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Réponse invalide', $result['error']);
    }

    public function testValidITSubjects(): void
    {
        $validSubjects = [
            'développement web',
            'programmation php',
            'base de données mysql',
            'sécurité informatique',
            'intelligence artificielle',
            'recrutement tech',
            'formation agile',
            'cloud computing',
            'devops',
            'startup innovation'
        ];

        foreach ($validSubjects as $subject) {
            // Vérifier que le sujet est accepté (ne retourne pas d'erreur de type)
            $this->assertStringNotContainsString('doit être lié', $this->getSubjectValidationError($subject));
        }
    }

    public function testInvalidSubjects(): void
    {
        $invalidSubjects = [
            'cuisine et recettes',
            'mode et tendances',
            'football et sport',
            'voyage et tourisme',
            'musique et concert'
        ];

        foreach ($invalidSubjects as $subject) {
            $this->assertStringContainsString('doit être lié', $this->getSubjectValidationError($subject));
        }
    }

    private function getSubjectValidationError(string $subject): string
    {
        $service = new ChatbotService($this->httpClient, 'test-key');
        $result = $service->generatePublicationSuggestion($subject, '');
        return $result['error'] ?? '';
    }
}
