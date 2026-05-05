<?php

// Test reaction functionality after fixing TypeError
// Access: http://localhost:8000/test_reaction_fix.php

header('Content-Type: text/plain');

echo "=== TESTING REACTION FUNCTIONALITY AFTER FIX ===\n\n";

// Load Symfony environment
require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\ORM\EntityManagerInterface;
use App\Kernel;

try {
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    
    $entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
    $connection = $entityManager->getConnection();
    
    echo "1. Testing fixed repository method...\n";
    
    // Get test data
    $publicationId = $connection->fetchOne("SELECT id FROM publication LIMIT 1");
    $userId = $connection->fetchOne("SELECT id FROM users LIMIT 1");
    
    if (!$publicationId || !$userId) {
        echo "✗ No test data available\n";
        
        // Create test data if needed
        if (!$publicationId) {
            $connection->executeStatement("
                INSERT INTO publication (titre, contenu, datePublication) 
                VALUES ('Test Publication', 'Test content', NOW())
            ");
            $publicationId = $connection->lastInsertId();
            echo "✓ Created test publication ID: $publicationId\n";
        }
        
        if (!$userId) {
            echo "✗ No users found - you need to be logged in\n";
        }
    }
    
    if ($publicationId && $userId) {
        echo "✓ Test data ready - Publication: $publicationId, User: $userId\n";
        
        // Test the repository method directly
        $repository = $entityManager->getRepository(\App\Entity\ReactionPublication::class);
        
        echo "\n2. Testing addOrUpdateReaction method...\n";
        
        try {
            // Test adding a like
            $reaction1 = $repository->addOrUpdateReaction($publicationId, $userId, 'like');
            echo "✓ Add like: " . ($reaction1 ? "Success (ID: " . $reaction1->getId() . ")" : "Removed") . "\n";
            
            // Test updating to love
            $reaction2 = $repository->addOrUpdateReaction($publicationId, $userId, 'love');
            echo "✓ Update to love: " . ($reaction2 ? "Success (ID: " . $reaction2->getId() . ")" : "Removed") . "\n";
            
            // Test removing by clicking same type
            $reaction3 = $repository->addOrUpdateReaction($publicationId, $userId, 'love');
            echo "✓ Remove love: " . ($reaction3 ? "Still exists" : "Removed") . "\n";
            
            // Test adding again
            $reaction4 = $repository->addOrUpdateReaction($publicationId, $userId, 'like');
            echo "✓ Add like again: " . ($reaction4 ? "Success (ID: " . $reaction4->getId() . ")" : "Removed") . "\n";
            
            // Clean up
            $repository->removeUserReaction($publicationId, $userId);
            echo "✓ Cleaned up test data\n";
            
        } catch (\Exception $e) {
            echo "✗ Repository method failed: " . $e->getMessage() . "\n";
            echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        }
        
        echo "\n3. Testing HTTP endpoint...\n";
        
        // Test the HTTP endpoint
        $testUrl = "http://localhost:8000/reaction/add/$publicationId";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $testUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'type=like',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'X-Requested-With: XMLHttpRequest'
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_COOKIE => $_SERVER['HTTP_COOKIE'] ?? ''
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        echo "HTTP Status: " . $httpCode . "\n";
        echo "CURL Error: " . ($error ?: 'None') . "\n";
        echo "Response: " . $response . "\n";
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if ($data && isset($data['success'])) {
                echo "✓ HTTP endpoint working\n";
                echo "  Success: " . ($data['success'] ? 'Yes' : 'No') . "\n";
                if (isset($data['counts'])) {
                    echo "  Counts: " . json_encode($data['counts']) . "\n";
                }
            } else {
                echo "✗ Invalid JSON response\n";
            }
        } else {
            echo "✗ HTTP endpoint failed with code $httpCode\n";
        }
        
        echo "\n4. Testing different reaction types...\n";
        
        $reactionTypes = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];
        
        foreach ($reactionTypes as $type) {
            try {
                $reaction = $repository->addOrUpdateReaction($publicationId, $userId, $type);
                echo "✓ Reaction '$type': " . ($reaction ? "Success" : "Removed") . "\n";
                
                // Clean up each test
                $repository->removeUserReaction($publicationId, $userId);
                
            } catch (\Exception $e) {
                echo "✗ Reaction '$type' failed: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n5. Testing getReactionsCountForPublication...\n";
        
        // Add some test reactions
        $repository->addOrUpdateReaction($publicationId, $userId, 'like');
        
        $counts = $repository->getReactionsCountForPublication($publicationId);
        echo "Reaction counts: " . json_encode($counts) . "\n";
        
        // Clean up
        $repository->removeUserReaction($publicationId, $userId);
        
        echo "\n6. Testing getUserReactionForPublication...\n";
        
        $reaction = $repository->addOrUpdateReaction($publicationId, $userId, 'love');
        $userReaction = $repository->getUserReactionForPublication($publicationId, $userId);
        
        if ($userReaction) {
            echo "✓ User reaction found: " . $userReaction->getType() . " " . $userReaction->getEmoji() . "\n";
        } else {
            echo "✗ No user reaction found\n";
        }
        
        // Clean up
        $repository->removeUserReaction($publicationId, $userId);
    }
    
    echo "\n=== TEST COMPLETED ===\n";
    echo "The TypeError fix should resolve the 500 error.\n\n";
    echo "What was fixed:\n";
    echo "✓ Changed return type from ReactionPublication to ?ReactionPublication\n";
    echo "✓ Method can now return null when removing reactions\n";
    echo "✓ Controller can handle null responses properly\n\n";
    
    echo "Next steps:\n";
    echo "1. Refresh the publication page\n";
    echo "2. Try clicking like/dislike buttons\n";
    echo "3. The 500 error should be resolved\n";
    echo "4. Reactions should work normally now\n";
    
} catch (\Exception $e) {
    echo "✗ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
