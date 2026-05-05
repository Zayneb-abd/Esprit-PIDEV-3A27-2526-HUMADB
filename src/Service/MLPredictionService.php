<?php

namespace App\Service;

/**
 * Service de prédiction Machine Learning pour la gestion des congés
 * Utilise Python avec scikit-learn via appel de commande
 */
class MLPredictionService
{
    private string $pythonPath = 'py';
    private string $mlScriptPath;
    private string $projectDir;
    
    public function __construct()
    {
        // Chemin vers le script Python
        $this->projectDir = __DIR__ . '/../..';
        $this->mlScriptPath = $this->projectDir . '/ml/predict_conge.py';
    }
    
    /**
     * Prédit la probabilité qu'un employé demande un congé prochainement
     * 
     * @param int $userId ID de l'utilisateur
     * @return array<string, mixed> Résultat de la prédiction
     */
    public function predictCongeProbability(int $userId): array
    {
        // Vérifier que le script existe
        if (!file_exists($this->mlScriptPath)) {
            return [
                'success' => false,
                'error' => 'Script ML non trouvé: ' . $this->mlScriptPath,
                'fallback' => $this->getSimplePrediction($userId)
            ];
        }
        
        // Exécuter le script Python
        $command = sprintf(
            '%s "%s" %d 2>&1',
            escapeshellcmd($this->pythonPath),
            escapeshellarg($this->mlScriptPath),
            $userId
        );
        
        $output = shell_exec($command);
        
        if ($output === null || empty($output)) {
            // Python non disponible, utiliser fallback
            return [
                'success' => true,
                'ml_prediction' => false,
                'data' => $this->getSimplePrediction($userId)
            ];
        }
        
        // Nettoyer la sortie (enlever BOM UTF-8 si présent)
        $output = preg_replace('/^\xEF\xBB\xBF/', '', $output);
        $output = mb_convert_encoding($output, 'UTF-8', 'UTF-8');
        
        // Extraire uniquement le JSON valide (entre accolades)
        if (preg_match('/\{.*\}/s', $output, $matches)) {
            $jsonOutput = $matches[0];
        } else {
            $jsonOutput = $output;
        }
        
        $result = json_decode($jsonOutput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Si pas de JSON valide, retourner le fallback
            return [
                'success' => true,
                'ml_prediction' => false,
                'data' => $this->getSimplePrediction($userId)
            ];
        }
        
        return [
            'success' => true,
            'ml_prediction' => true,
            'data' => $result
        ];
    }
    
    /**
     * Prédiction simple sans ML (fallback)
     * Basé sur l'historique simple
     */
    private function getSimplePrediction(int $userId): array
    {
        // Simuler une prédiction basée sur la saisonnalité
        $currentMonth = (int) date('n');
        
        // Probabilités par mois (saisonnalité des congés)
        $seasonality = [
            1 => 0.3, 2 => 0.2, 3 => 0.2, 4 => 0.3,
            5 => 0.4, 6 => 0.6, 7 => 0.8, 8 => 0.7,
            9 => 0.3, 10 => 0.3, 11 => 0.4, 12 => 0.5
        ];
        
        // Déterminer la période recommandée (mois avec faible probabilité)
        $recommendedMonths = [9, 10, 11, 1, 2, 3]; // Sept-Mars (hors été/fêtes)
        $recommendedMonth = $recommendedMonths[array_rand($recommendedMonths)];
        $monthNames = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        return [
            'user_id' => $userId,
            'probability_conge' => $seasonality[$currentMonth] ?? 0.3,
            'confidence' => 0.5,
            'method' => 'seasonality_fallback',
            'note' => 'Prédiction basée sur la saisonnalité (ML non disponible)',
            'recommended_period' => $monthNames[$recommendedMonth],
            'suggested_duration' => 5
        ];
    }
    
    /**
     * Suggère la meilleure période pour un congé
     * Basé sur la charge de l'équipe et l'historique
     * 
     * @param int $userId
     * @param int $duration Nombre de jours souhaités
     * @return array<string, mixed>
     */
    public function suggestBestPeriod(int $userId, int $duration): array
    {
        // Mois à éviter (forte charge)
        $highLoadMonths = [6, 7, 8, 12]; // Été + Décembre
        $currentMonth = (int) date('n');
        
        // Suggérer un mois avec faible charge
        $suggestions = [];
        for ($i = 1; $i <= 12; $i++) {
            $month = (($currentMonth - 1 + $i) % 12) + 1;
            if (!in_array($month, $highLoadMonths)) {
                $suggestions[] = [
                    'month' => $month,
                    'month_name' => $this->getMonthName($month),
                    'score' => 100 - (in_array($month, [1, 2]) ? 20 : 0), // Bonus hiver
                    'reason' => 'Faible charge équipe'
                ];
            }
        }
        
        // Trier par score
        usort($suggestions, fn($a, $b) => $b['score'] <=> $a['score']);
        
        return [
            'user_id' => $userId,
            'requested_duration' => $duration,
            'suggestions' => array_slice($suggestions, 0, 3),
            'best_period' => $suggestions[0] ?? null,
            'avoid_periods' => 'Juillet-Août (congés scolaires), Décembre (fêtes)'
        ];
    }
    
    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        return $months[$month] ?? 'Inconnu';
    }

    /**
     * 🔮 Prédiction d'engagement pour une publication
     * Prédit le nombre de likes et commentaires
     */
    public function predictPublicationEngagement(string $content, string $type = ''): array
    {
        $scriptPath = $this->projectDir . '/ml/predict_engagement.py';
        
        // Échapper le contenu pour la ligne de commande
        $escapedContent = escapeshellarg($content);
        $escapedType = escapeshellarg($type);
        
        $command = sprintf(
            '%s %s %s %s 2>&1',
            $this->pythonPath,
            escapeshellcmd($scriptPath),
            $escapedContent,
            $escapedType
        );
        
        $output = shell_exec($command);
        
        if ($output === null) {
            return [
                'success' => false,
                'error' => 'Erreur d\'exécution du script ML',
                'fallback' => $this->getBasicEngagementPrediction($content)
            ];
        }
        
        // Nettoyer la sortie
        $output = preg_replace('/^\xEF\xBB\xBF/', '', $output);
        $output = mb_convert_encoding($output, 'UTF-8', 'UTF-8');
        
        // Extraire uniquement le JSON valide
        if (preg_match('/\{.*\}/s', $output, $matches)) {
            $jsonOutput = $matches[0];
        } else {
            $jsonOutput = $output;
        }
        
        $result = json_decode($jsonOutput, true);
        
        if ($result === null) {
            return [
                'success' => true,
                'data' => $this->getBasicEngagementPrediction($content)
            ];
        }
        
        if (isset($result['error'])) {
            return [
                'success' => true,
                'data' => $this->getBasicEngagementPrediction($content)
            ];
        }
        
        return [
            'success' => true,
            'data' => $result
        ];
    }

    /**
     * 📊 Prédiction basique d'engagement (fallback)
     */
    private function getBasicEngagementPrediction(string $content): array
    {
        $length = strlen($content);
        $hasQuestion = strpos($content, '?') !== false;
        $hasMedia = strpos(strtolower($content), 'image') !== false || 
                    strpos(strtolower($content), 'photo') !== false ||
                    strpos(strtolower($content), 'video') !== false;
        
        $score = 5.0;
        if ($hasMedia) $score += 2.0;
        if ($hasQuestion) $score += 1.0;
        if ($length >= 100 && $length <= 500) $score += 1.0;
        
        return [
            'engagement_score' => min(10, max(1, $score)),
            'estimated_likes' => (int)($score * 8),
            'estimated_comments' => (int)($score * 2),
            'method' => 'basic_php_fallback'
        ];
    }

    /**
     * 💬 Prédiction d'engagement pour un commentaire
     * Prédit les réactions et réponses
     */
    public function predictCommentEngagement(string $content): array
    {
        $scriptPath = $this->projectDir . '/ml/predict_comment_engagement.py';
        
        $escapedContent = escapeshellarg($content);
        
        $command = sprintf(
            '%s %s %s 2>&1',
            $this->pythonPath,
            escapeshellcmd($scriptPath),
            $escapedContent
        );
        
        $output = shell_exec($command);
        
        if ($output === null) {
            return [
                'success' => false,
                'error' => 'Erreur d\'exécution du script ML',
                'fallback' => $this->getBasicCommentEngagementPrediction($content)
            ];
        }
        
        // Nettoyer la sortie
        $output = preg_replace('/^\xEF\xBB\xBF/', '', $output);
        $output = mb_convert_encoding($output, 'UTF-8', 'UTF-8');
        
        // Extraire uniquement le JSON valide
        if (preg_match('/\{.*\}/s', $output, $matches)) {
            $jsonOutput = $matches[0];
        } else {
            $jsonOutput = $output;
        }
        
        $result = json_decode($jsonOutput, true);
        
        if ($result === null) {
            return [
                'success' => true,
                'data' => $this->getBasicCommentEngagementPrediction($content)
            ];
        }
        
        if (isset($result['error'])) {
            return [
                'success' => true,
                'data' => $this->getBasicCommentEngagementPrediction($content)
            ];
        }
        
        return [
            'success' => true,
            'data' => $result
        ];
    }

    /**
     * 💬 Prédiction basique d'engagement pour commentaire (fallback)
     */
    private function getBasicCommentEngagementPrediction(string $content): array
    {
        $length = strlen($content);
        $hasQuestion = strpos($content, '?') !== false;
        $hasEmoji = preg_match('/[^\x00-\x7F]/', $content);
        
        $score = 5.0;
        if ($hasEmoji) $score += 1.0;
        if ($hasQuestion) $score += 1.5;
        if ($length >= 20 && $length <= 200) $score += 1.0;
        
        return [
            'engagement_score' => min(10, max(1, $score)),
            'estimated_reactions' => (int)($score * 1.5),
            'estimated_responses' => (int)($score * 0.5),
            'method' => 'basic_php_fallback'
        ];
    }
}
