<?php

// Test de la clé API Groq
echo "🔑 Test de la clé API Groq\n";
echo "=====================================\n\n";

// Récupérer la clé depuis l'environnement
$groqApiKey = $_ENV['GROQ_API_KEY'] ?? '';

if (empty($groqApiKey)) {
    echo "❌ ERREUR: Clé API Groq non trouvée dans l'environnement\n";
    echo "💡 Solution: Ajoutez GROQ_API_KEY=votre_clé dans votre fichier .env\n";
    exit(1);
}

echo "✅ Clé API trouvée: " . substr($groqApiKey, 0, 20) . "...\n\n";

// Test simple de connexion à l'API Groq
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.groq.com/openai/v1/models');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $groqApiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

echo "📡 Test de connexion à l'API Groq...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ SUCCÈS: Connexion à l'API Groq réussie !\n";
    echo "📊 Réponse: " . substr($response, 0, 100) . "...\n";
    echo "🎯 Votre clé API est valide et fonctionnelle\n";
} else {
    echo "❌ ERREUR: Échec de connexion à l'API Groq\n";
    echo "📊 Code HTTP: " . $httpCode . "\n";
    echo "📊 Réponse: " . substr($response, 0, 200) . "\n";
    
    // Analyse des erreurs courantes
    switch ($httpCode) {
        case 401:
            echo "💡 Solution: Votre clé API est invalide ou a expiré\n";
            echo "🔗 Vérifiez votre clé sur: https://console.groq.com/keys\n";
            break;
        case 429:
            echo "💡 Solution: Limite de débit dépassée\n";
            echo "⏱️ Attendez 1 minute avant de réessayer\n";
            break;
        case 403:
            echo "💡 Solution: Accès refusé\n";
            echo "🔗 Vérifiez les permissions de votre clé API\n";
            break;
        default:
            echo "💡 Solution: Vérifiez votre connexion internet\n";
            echo "🔗 Contactez le support Groq si le problème persiste\n";
    }
}

echo "\n=====================================\n";
echo "🎯 Prochaines étapes:\n";
echo "1. ✅ Si le test est SUCCÈS, votre chatbot fonctionnera parfaitement\n";
echo "2. 🚀 Allez sur /admin/publication/new pour tester le chatbot\n";
echo "3. 📖 Consultez CHATBOT_GUIDE.md pour plus d'informations\n\n";

echo "🎉 Test terminé !\n";
