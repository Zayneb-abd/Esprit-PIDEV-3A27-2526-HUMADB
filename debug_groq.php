<?php

// Script de débuggage pour l'API Groq
echo "🔍 Débogage de l'API Groq\n";
echo "=====================================\n\n";

// Test avec curl pour voir la requête exacte
$data = [
    'model' => 'llama3-8b-8192',
    'messages' => [
        [
            'role' => 'system',
            'content' => 'Tu es un assistant expert en communication d\'entreprise. Aide à créer des publications professionnelles, engageantes et adaptées au contexte d\'entreprise.'
        ],
        [
            'role' => 'user',
            'content' => 'Crée une suggestion de publication d\'entreprise sur le sujet : Test sujet. Contexte supplémentaire : Test contexte.'
        ]
    ],
    'max_tokens' => 500,
    'temperature' => 0.7
];

$jsonData = json_encode($data, JSON_PRETTY_PRINT);

echo "📤 Données JSON envoyées à l'API:\n";
echo $jsonData . "\n\n";

// Test avec curl
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.groq.com/openai/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer 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',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

echo "📡 Envoi de la requête...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "📊 Code HTTP: " . $httpCode . "\n";
echo "📄 Réponse brute:\n";
echo $response . "\n\n";

if ($httpCode === 200) {
    $responseData = json_decode($response, true);
    echo "✅ SUCCÈS: Réponse reçue\n";
    echo "📝 Contenu de la réponse:\n";
    if (isset($responseData['choices'][0]['message']['content'])) {
        echo $responseData['choices'][0]['message']['content'] . "\n\n";
    }
    if (isset($responseData['usage'])) {
        echo "🔑 Tokens utilisés: " . ($responseData['usage']['total_tokens'] ?? 'N/A') . "\n";
    }
} else {
    echo "❌ ERREUR: Échec de la requête\n";
    
    // Analyse des erreurs 400 courantes
    if ($httpCode === 400) {
        echo "\n🔍 Analyse de l'erreur 400:\n";
        $responseArray = json_decode($response, true);
        if (isset($responseArray['error'])) {
            echo "Message d'erreur: " . $responseArray['error']['message'] . "\n";
            echo "Type d'erreur: " . $responseArray['error']['type'] . "\n";
            
            if (strpos($responseArray['error']['message'], 'model') !== false) {
                echo "💡 Problème: Modèle invalide ou non supporté\n";
            }
            if (strpos($responseArray['error']['message'], 'invalid') !== false) {
                echo "💡 Problème: Format de la requête invalide\n";
            }
            if (strpos($responseArray['error']['message'], 'context') !== false) {
                echo "💡 Problème: Contexte ou prompt trop long\n";
            }
        }
    }
}

echo "\n=====================================\n";
echo "🎯 Recommandations:\n";
echo "1. ✅ Vérifiez que votre clé API est valide\n";
echo "2. ✅ Assurez-vous que le modèle 'llama3-8b-8192' est correct\n";
echo "3. ✅ Vérifiez la longueur du prompt (< 4000 caractères)\n";
echo "4. ✅ Testez avec des requêtes plus simples\n";
