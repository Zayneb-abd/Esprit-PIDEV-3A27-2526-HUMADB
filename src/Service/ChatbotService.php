<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatbotService
{
    private $httpClient;
    private $groqApiKey;

    public function __construct(HttpClientInterface $httpClient, string $groqApiKey)
    {
        $this->httpClient = $httpClient;
        $this->groqApiKey = $groqApiKey;
    }

    /**
     * Génère une suggestion de publication basée sur le sujet
     */
    public function generatePublicationSuggestion(string $subject, string $context = ''): array
    {
        if (empty($this->groqApiKey)) {
            return [
                'success' => false,
                'error' => 'Clé Groq API non configurée',
                'suggestion' => null
            ];
        }

        $prompt = $this->buildPrompt($subject, $context);
        
        try {
            return $this->callGroqAPI($prompt);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur de connexion à l\'API Groq: ' . $e->getMessage(),
                'suggestion' => null
            ];
        }
    }

    /**
     * Appelle l'API Groq
     */
    private function callGroqAPI(string $prompt): array
    {
        try {
            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.1-8b-instant',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un assistant expert en communication d\'entreprise. Aide à créer des publications professionnelles, engageantes et adaptées au contexte d\'entreprise.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'max_tokens' => 500,
                    'temperature' => 0.7
                ]
            ]);

            $data = $response->toArray();
            
            if (isset($data['choices'][0]['message']['content'])) {
                $suggestion = $data['choices'][0]['message']['content'];
                
                return [
                    'success' => true,
                    'suggestion' => $suggestion,
                    'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                    'provider' => 'Groq'
                ];
            }

            return [
                'success' => false,
                'error' => 'Réponse invalide de l\'API Groq',
                'suggestion' => null,
                'provider' => 'Groq'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur de connexion à l\'API Groq: ' . $e->getMessage(),
                'suggestion' => null,
                'provider' => 'Groq'
            ];
        }
    }

    /**
     * Construit le prompt pour l'API
     */
    private function buildPrompt(string $subject, string $context): string
    {
        $basePrompt = "Crée une suggestion de publication d'entreprise sur le sujet : '{$subject}'.";
        
        if (!empty($context)) {
            $basePrompt .= " Contexte supplémentaire : {$context}";
        }

        $basePrompt .= "

La publication doit :
- Être professionnelle et engageante
- Avoir entre 50 et 300 mots
- Être adaptée aux réseaux sociaux d'entreprise
- Inclure un appel à l'action si approprié
- Utiliser un ton positif et motivant

Format de réponse :
Titre : [titre accrocheur]
Contenu : [contenu de la publication]
Hashtags : [3-5 hashtags pertinents]
Type : [actualité/annonce/motivation/formation]";

        return $basePrompt;
    }
}
