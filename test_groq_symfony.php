<?php

require_once 'vendor/autoload.php';

use App\Service\ChatbotService;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

echo "🤖 Test du Chatbot avec Symfony\n";
echo "=====================================\n\n";

// Créer le service comme Symfony le ferait
$httpClient = HttpClient::create();
$params = new ParameterBag([
    'GROQ_API_KEY' => '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'
]);

$chatbotService = new ChatbotService($httpClient, $params->get('GROQ_API_KEY'));

echo "📋 Configuration Symfony:\n";
echo "✅ Clé API: " . $params->get('GROQ_API_KEY') . "\n";
echo "✅ Service Chatbot: Créé\n\n";

// Test 1: Génération de suggestion
echo "📝 Test 1: Génération de suggestion\n";
echo "Sujet: 'Nouveau collaborateur'\n\n";

$result1 = $chatbotService->generatePublicationSuggestion('Nouveau collaborateur', 'Développeur Symfony avec 3 ans d\'expérience');

if ($result1['success']) {
    echo "✅ SUCCÈS: Suggestion générée\n";
    echo "📄 Suggestion:\n";
    echo $result1['suggestion'] . "\n\n";
    echo "🔑 Tokens utilisés: " . ($result1['tokens_used'] ?? 'N/A') . "\n";
    echo "🏢 Provider: " . ($result1['provider'] ?? 'N/A') . "\n\n";
} else {
    echo "❌ ERREUR: " . $result1['error'] . "\n\n";
}

echo "=====================================\n\n";

// Test 2: Test avec sujet vide
echo "📝 Test 2: Test avec sujet vide\n";
echo "Sujet: ''\n\n";

$result2 = $chatbotService->generatePublicationSuggestion('', '');

if (!$result2['success']) {
    echo "✅ SUCCÈS: Erreur correctement gérée\n";
    echo "❌ Erreur attendue: " . $result2['error'] . "\n\n";
} else {
    echo "❌ ERREUR: Le sujet vide aurait dû être rejeté\n\n";
}

echo "=====================================\n\n";

echo "🎯 Test terminé !\n";
echo "💡 Si les tests sont ✅ SUCCÈS, votre chatbot fonctionne parfaitement\n";
echo "🚀 Allez sur /admin/publication/new pour utiliser l'interface\n";
echo "📖 Consultez CHATBOT_GUIDE.md pour plus d'informations\n\n";
