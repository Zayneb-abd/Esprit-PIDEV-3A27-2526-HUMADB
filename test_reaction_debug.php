<?php

require_once 'vendor/autoload.php';

use App\Repository\ReactionPublicationRepository;
use Doctrine\ORM\EntityManagerInterface;

// Test simple pour diagnostiquer le problème de réaction
echo "=== Test de débogage des réactions ===\n\n";

// Simuler une requête de réaction
$publicationId = 36;
$userId = 1;
$type = 'like';

echo "Données de test:\n";
echo "Publication ID: $publicationId\n";
echo "User ID: $userId\n";
echo "Type: $type\n\n";

// Test 1: Vérifier si l'entité ReactionPublication fonctionne
try {
    $reaction = new \App\Entity\ReactionPublication();
    echo "1. Entity ReactionPublication: OK\n";
} catch (Exception $e) {
    echo "1. Entity ReactionPublication: ERROR - " . $e->getMessage() . "\n";
}

// Test 2: Vérifier les méthodes de l'entité
try {
    $reaction = new \App\Entity\ReactionPublication();
    $reaction->setType('like');
    echo "2. setType(): OK\n";
} catch (Exception $e) {
    echo "2. setType(): ERROR - " . $e->getMessage() . "\n";
}

// Test 3: Vérifier les constantes
try {
    echo "3. Constants:\n";
    echo "   TYPE_LIKE: " . \App\Entity\ReactionPublication::TYPE_LIKE . "\n";
    echo "   TYPE_DISLIKE: " . \App\Entity\ReactionPublication::TYPE_DISLIKE . "\n";
} catch (Exception $e) {
    echo "3. Constants: ERROR - " . $e->getMessage() . "\n";
}

// Test 4: Vérifier les méthodes de l'entité
try {
    $reaction = new \App\Entity\ReactionPublication();
    echo "4. Entity methods:\n";
    echo "   isLike(): " . ($reaction->isLike() ? 'true' : 'false') . "\n";
    echo "   isDislike(): " . ($reaction->isDislike() ? 'true' : 'false') . "\n";
    echo "   getEmoji(): " . $reaction->getEmoji() . "\n";
} catch (Exception $e) {
    echo "4. Entity methods: ERROR - " . $e->getMessage() . "\n";
}

echo "\n=== Test terminé ===\n";
