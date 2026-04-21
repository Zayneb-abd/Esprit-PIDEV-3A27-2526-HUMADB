<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AIService
{
    private HttpClientInterface $httpClient;
    private string $groqApiKey;

    public function __construct(HttpClientInterface $httpClient, string $groqApiKey)
    {
        $this->httpClient = $httpClient;
        $this->groqApiKey = $groqApiKey;
    }

    public function generateFormationDescription(string $formationTitle): string
    {
        $prompt = "Génère une description courte et concise (2-3 phrases maximum) pour la formation : '$formationTitle'. 
        Sois direct et informatif. En français uniquement.";

        $apiResponse = $this->callGroqAPI($prompt);
        
        // If API call failed, generate fallback description
        if (strpos($apiResponse, 'Erreur') !== false || strpos($apiResponse, 'Description non disponible') !== false) {
            return $this->generateFallbackDescription($formationTitle);
        }
        
        return $apiResponse;
    }
    
    private function generateFallbackDescription(string $formationTitle): string
    {
        $descriptions = [
            'dev python' => 'Formation Python complète : apprenez les fondamentaux, Django, Flask et développez des applications professionnelles.',
            'javascript' => 'Formation JavaScript moderne : maîtrisez ES6+, React/Vue.js et créez des applications web interactives.',
            'php' => 'Formation PHP professionnelle : apprenez Symfony, MySQL et les bonnes pratiques de développement web.',
            'default' => 'Formation professionnelle intensive pour développer vos compétences techniques et pratiques.'
        ];
        
        $titleLower = strtolower($formationTitle);
        foreach ($descriptions as $key => $description) {
            if ($key !== 'default' && strpos($titleLower, $key) !== false) {
                return $description;
            }
        }
        
        return $descriptions['default'];
    }

    public function generateFormationObjectives(string $formationTitle, string $formationDescription = null): array
    {
        $context = $formationDescription ? "Basé sur cette description : '$formationDescription'" : "";
        $prompt = "Génère 5-8 objectifs pédagogiques spécifiques et mesurables pour la formation : '$formationTitle'. $context. 
        Les objectifs doivent être directement liés au contenu de la formation. Chaque objectif doit commencer par un verbe d'action et être concis. 
        Réponds UNIQUEMENT avec une liste JSON valide, sans autre texte : [\"objectif1\", \"objectif2\", \"objectif3\"]";

        $response = $this->callGroqAPI($prompt);
        
        // Clean response to extract JSON if there's extra text
        $jsonStart = strpos($response, '[');
        $jsonEnd = strrpos($response, ']');
        if ($jsonStart !== false && $jsonEnd !== false) {
            $response = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        }
        
        // Parse JSON response
        $objectives = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($objectives) || empty($objectives)) {
            // If AI fails, try again with simpler prompt
            return $this->generateObjectivesWithRetry($formationTitle, $formationDescription);
        }

        return array_filter($objectives, function($obj) {
            return is_string($obj) && strlen(trim($obj)) > 0;
        });
    }
    
    private function generateObjectivesWithRetry(string $formationTitle, string $formationDescription = null): array
    {
        $simplePrompt = "Génère 5 objectifs pour la formation '$formationTitle'. Réponds avec uniquement une liste JSON : [\"obj1\", \"obj2\", \"obj3\", \"obj4\", \"obj5\"]";
        
        $response = $this->callGroqAPI($simplePrompt);
        
        // Clean response
        $jsonStart = strpos($response, '[');
        $jsonEnd = strrpos($response, ']');
        if ($jsonStart !== false && $jsonEnd !== false) {
            $response = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        }
        
        $objectives = json_decode($response, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($objectives) && !empty($objectives)) {
            return array_filter($objectives, function($obj) {
                return is_string($obj) && strlen(trim($obj)) > 0;
            });
        }
        
        // Last resort: generate unique objectives based on title
        return $this->generateUniqueObjectives($formationTitle);
    }
    
    private function generateUniqueObjectives(string $formationTitle): array
    {
        $titleLower = strtolower($formationTitle);
        
        // Generate objectives based on keywords in the title
        if (strpos($titleLower, 'python') !== false) {
            return [
                "Installer et configurer l'environnement de développement Python",
                "Maîtriser les structures de données et les algorithmes Python",
                "Développer des applications web avec Django ou Flask",
                "Implémenter les tests unitaires et le débogage Python",
                "Créer des API REST avec Python"
            ];
        } elseif (strpos($titleLower, 'java') !== false) {
            return [
                "Maîtriser les concepts de la programmation orientée objet en Java",
                "Développer des applications avec Spring Boot",
                "Gérer les bases de données avec JDBC et JPA",
                "Implémenter les design patterns en Java",
                "Créer des applications web avec Java EE"
            ];
        } elseif (strpos($titleLower, 'javascript') !== false) {
            return [
                "Maîtriser les fondamentaux du JavaScript moderne",
                "Développer des interfaces avec React ou Vue.js",
                "Implémenter les applications web full-stack",
                "Optimiser les performances JavaScript",
                "Utiliser les outils de build et de déploiement"
            ];
        } elseif (strpos($titleLower, 'php') !== false) {
            return [
                "Maîtriser la programmation PHP avancée",
                "Développer avec le framework Symfony",
                "Créer des applications web sécurisées",
                "Gérer les bases de données avec PHP",
                "Développer des API REST avec PHP"
            ];
        } else {
            // Generate generic but unique objectives
            return [
                "Comprendre les concepts fondamentaux de {$formationTitle}",
                "Appliquer les techniques pratiques en {$formationTitle}",
                "Développer des projets réels avec {$formationTitle}",
                "Maîtriser les outils et technologies de {$formationTitle}",
                "Évaluer et améliorer les compétences en {$formationTitle}"
            ];
        }
    }
    
    private function generateFallbackObjectives(string $formationTitle): array
    {
        $objectivesMap = [
            'dev python' => [
                "Installer et configurer l'environnement Python",
                "Maîtriser les structures de données et algorithmes",
                "Développer des applications avec Django/Flask",
                "Implémenter les bonnes pratiques de codage Python",
                "Créer et gérer des bases de données avec Python"
            ],
            'javascript' => [
                "Maîtriser les concepts fondamentaux de JavaScript",
                "Utiliser ES6+ et les fonctionnalités modernes",
                "Développer avec React ou Vue.js",
                "Créer des applications web interactives",
                "Optimiser les performances JavaScript"
            ],
            'php' => [
                "Maîtriser la programmation orientée objet en PHP",
                "Développer avec le framework Symfony",
                "Gérer les bases de données MySQL avec PHP",
                "Implémenter les mesures de sécurité web",
                "Créer des API REST avec PHP"
            ],
            'default' => [
                "Comprendre les concepts fondamentaux",
                "Maîtriser les techniques pratiques",
                "Appliquer les connaissances en projet",
                "Évaluer les résultats obtenus",
                "Améliorer continuellement les compétences"
            ]
        ];
        
        $titleLower = strtolower($formationTitle);
        foreach ($objectivesMap as $key => $objectives) {
            if ($key !== 'default' && strpos($titleLower, $key) !== false) {
                return $objectives;
            }
        }
        
        return $objectivesMap['default'];
    }

    private function callGroqAPI(string $prompt): string
    {
        $url = 'https://api.groq.com/openai/v1/chat/completions';
        
        $payload = [
            'model' => 'llama-3.1-8b-instant',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1000
        ];

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 30
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                return 'Erreur API: Code ' . $statusCode;
            }

            $data = $response->toArray();
            
            if (isset($data['choices'][0]['message']['content'])) {
                return trim($data['choices'][0]['message']['content']);
            }
            
            return 'Format de réponse invalide';
            
        } catch (\Exception $e) {
            return 'Erreur de connexion: ' . $e->getMessage();
        }
    }
}
