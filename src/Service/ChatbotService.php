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

        // Valider que le sujet est lié au domaine IT, RH ou entreprise informatique
        if (!$this->isITRelatedSubject($subject)) {
            return [
                'success' => false,
                'error' => 'Le sujet doit être lié au domaine informatique/IT, ressources humaines ou entreprise technologique. Les sujets comme la cuisine, la mode, le sport, etc. ne sont pas acceptés.',
                'suggestion' => null
            ];
        }

        $prompt = $this->buildPrompt($subject, $context);
        
        try {
            $result = $this->callGroqAPI($prompt);
            
            // Validation supplémentaire de la réponse
            if ($result['success'] && !$this->isITRelatedContent($result['suggestion'])) {
                return [
                    'success' => false,
                    'error' => 'Le contenu généré ne semble pas être lié au domaine informatique, RH ou entreprise technologique. Veuillez choisir un sujet approprié.',
                    'suggestion' => null
                ];
            }
            
            return $result;
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
- Être liée au domaine informatique/IT, ressources humaines ou entreprise technologique (développement, réseaux, cybersécurité, cloud, IA, DevOps, RH, management, formation, etc.)

Format de réponse :
Titre : [titre accrocheur]
Contenu : [contenu de la publication]
Hashtags : [3-5 hashtags pertinents]
Type : [actualité/annonce/motivation/formation]";

        return $basePrompt;
    }

    /**
     * Vérifie si le sujet est lié au domaine IT, RH ou entreprise technologique
     */
    private function isITRelatedSubject(string $subject): bool
    {
        $subjectLower = strtolower($subject);
        
        // Liste des mots-clés IT acceptés
        $itKeywords = [
            'développement', 'development', 'programmation', 'programming',
            'logiciel', 'software', 'application', 'app',
            'web', 'site', 'frontend', 'backend', 'fullstack',
            'base de données', 'database', 'sql', 'mysql', 'postgresql',
            'réseau', 'network', 'sécurité', 'security', 'cybersécurité',
            'cloud', 'aws', 'azure', 'gcp', 'serveur', 'server',
            'intelligence artificielle', 'ia', 'ai', 'machine learning', 'ml',
            'data', 'données', 'big data', 'analytics',
            'devops', 'ci/cd', 'docker', 'kubernetes', 'git',
            'api', 'rest', 'graphql', 'microservices',
            'mobile', 'android', 'ios', 'flutter', 'react native',
            'framework', 'symfony', 'laravel', 'react', 'vue', 'angular',
            'javascript', 'php', 'python', 'java', 'c++', 'c#', 'go', 'rust',
            'algorithme', 'algorithm', 'structure de données', 'data structure',
            'test', 'testing', 'qualité', 'quality', 'agile', 'scrum',
            'blockchain', 'crypto', 'iot', 'internet des objets',
            'virtualisation', 'container', 'kubernetes', 'docker',
            'linux', 'windows', 'macos', 'système', 'operating system',
            'bug', 'erreur', 'debug', 'débogage',
            'performance', 'optimisation', 'scalabilité', 'scalability',
            'architecture', 'design pattern', 'clean code',
            'version', 'git', 'github', 'gitlab', 'bitbucket',
            'documentation', 'tech', 'technologie', 'informatique',
            'it', 'tech', 'digital', 'numérique'
        ];
        
        // Liste des mots-clés RH acceptés
        $hrKeywords = [
            'ressources humaines', 'rh', 'hr', 'recrutement', 'recruitment',
            'embauche', 'emploi', 'carrière', 'career',
            'formation', 'training', 'développement des compétences', 'skill development',
            'management', 'manager', 'leadership', 'équipe', 'team',
            'collaborateur', 'employé', 'employee', 'talent', 'talents',
            'entretien', 'interview', 'évaluation', 'performance review',
            'motivation', 'engagement', 'culture', 'culture d\'entreprise',
            'organisation', 'organisation du travail', 'work organization',
            'avantages', 'social', 'paie', 'salaire', 'compensation',
            'congé', 'absence', 'présence', 'planning', 'planning',
            'onboarding', 'intégration', 'offboarding', 'départ',
            'diversité', 'inclusion', 'égalité', 'diversity inclusion',
            'bien-être', 'well-being', 'qualité de vie', 'work life balance'
        ];
        
        // Liste des mots-clés entreprise technologique acceptés
        $businessKeywords = [
            'entreprise', 'business', 'startup', 'innovation',
            'stratégie', 'strategy', 'croissance', 'growth',
            'transformation', 'digitalisation', 'modernisation',
            'produit', 'product', 'service', 'client', 'customer',
            'marché', 'market', 'concurrence', 'competition',
            'projet', 'project', 'livraison', 'delivery',
            'processus', 'process', 'optimisation', 'efficacité',
            'qualité', 'quality', 'excellence', 'amélioration',
            'budget', 'coût', 'rentabilité', 'profitability',
            'investissement', 'roi', 'return on investment',
            'partenariat', 'partnership', 'collaboration',
            'communication', 'interne', 'externe',
            'présentation', 'réunion', 'meeting'
        ];
        
        // Fusionner tous les mots-clés acceptés
        $allKeywords = array_merge($itKeywords, $hrKeywords, $businessKeywords);
        
        // Vérifier si au moins un mot-clé accepté est présent
        foreach ($allKeywords as $keyword) {
            if (str_contains($subjectLower, $keyword)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Vérifie si le contenu généré est lié au domaine IT, RH ou entreprise technologique
     */
    private function isITRelatedContent(string $content): bool
    {
        $contentLower = strtolower($content);
        
        // Liste de mots-clés non-IT à rejeter
        $nonITKeywords = [
            'cuisine', 'recette', 'plat', 'manger', 'restaurant', 'food',
            'mode', 'vêtement', 'habillement', 'tendance', 'style',
            'sport', 'football', 'basketball', 'tennis', 'gym', 'fitness',
            'musique', 'chanson', 'concert', 'album', 'artiste',
            'film', 'cinéma', 'série', 'netflix', 'acteur',
            'voyage', 'tourisme', 'destination', 'hôtel', 'plage',
            'beauté', 'maquillage', 'cosmétique', 'coiffure',
            'jardinage', 'plante', 'fleur', 'décoration',
            'automobile', 'voiture', 'moto', 'conduite',
            'finance personnelle', 'bourse', 'investissement', 'crypto-monnaie',
            'santé', 'médicament', 'maladie', 'médecin', 'hôpital',
            'politique', 'élection', 'gouvernement', 'loi',
            'religion', 'spiritualité', 'croyance'
        ];
        
        // Vérifier si des mots-clés non-IT sont présents
        $nonITCount = 0;
        foreach ($nonITKeywords as $keyword) {
            if (str_contains($contentLower, $keyword)) {
                $nonITCount++;
            }
        }
        
        // Si trop de mots-clés non-IT, rejeter
        if ($nonITCount >= 3) {
            return false;
        }
        
        // Vérifier la présence de mots-clés IT positifs
        $itKeywords = [
            'développement', 'logiciel', 'application', 'web', 'base de données',
            'réseau', 'sécurité', 'cloud', 'intelligence artificielle', 'data',
            'devops', 'api', 'framework', 'code', 'programmation', 'algorithme',
            'test', 'agile', 'blockchain', 'iot', 'docker', 'kubernetes',
            'git', 'version', 'documentation', 'tech', 'informatique', 'it'
        ];
        
        // Vérifier la présence de mots-clés RH positifs
        $hrKeywords = [
            'ressources humaines', 'rh', 'recrutement', 'embauche', 'carrière',
            'formation', 'management', 'équipe', 'collaborateur', 'employé',
            'talent', 'entretien', 'évaluation', 'motivation', 'culture',
            'organisation', 'avantages', 'congé', 'planning', 'onboarding'
        ];
        
        // Vérifier la présence de mots-clés entreprise positifs
        $businessKeywords = [
            'entreprise', 'business', 'startup', 'innovation', 'stratégie',
            'croissance', 'transformation', 'produit', 'service', 'client',
            'marché', 'projet', 'processus', 'qualité', 'budget', 'investissement'
        ];
        
        // Fusionner tous les mots-clés acceptés
        $allKeywords = array_merge($itKeywords, $hrKeywords, $businessKeywords);
        
        $acceptedCount = 0;
        foreach ($allKeywords as $keyword) {
            if (str_contains($contentLower, $keyword)) {
                $acceptedCount++;
            }
        }
        
        // Accepter si au moins un mot-clé accepté est présent
        return $acceptedCount >= 1;
    }
}
