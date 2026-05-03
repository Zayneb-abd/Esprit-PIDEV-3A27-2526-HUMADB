<?php

echo "🔍 Vérification des variables d'environnement\n";
echo "=========================================\n\n";

// Charger le fichier .env
$envFile = '.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    $lines = explode("\n", $envContent);
    
    foreach ($lines as $line) {
        if (strpos($line, 'GROQ_API_KEY=') !== false) {
            echo "✅ Trouvée: " . trim($line) . "\n";
        }
    }
} else {
    echo "❌ Fichier .env non trouvé\n";
}

echo "\n🔑 Test de la clé API\n";
echo "=========================\n\n";

$groqKey = getenv('GROQ_API_KEY');
if ($groqKey) {
    echo "✅ Clé API Groq chargée: " . substr($groqKey, 0, 20) . "...\n";
} else {
    echo "❌ Clé API Groq non disponible\n";
}

echo "\n🌐 Variables d'environnement actuelles:\n";
echo "GROQ_API_KEY: " . (getenv('GROQ_API_KEY') ? 'Définie' : 'Non définie') . "\n";
echo "CHATBOT_API_KEY: " . (getenv('CHATBOT_API_KEY') ? 'Définie' : 'Non définie') . "\n";
echo "CHATBOT_PROVIDER: " . (getenv('CHATBOT_PROVIDER') ? 'Définie' : 'Non définie') . "\n";
