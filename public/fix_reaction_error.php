<?php

// Fix reaction error - create missing table and fix issues
// Access: http://localhost:8000/fix_reaction_error.php

header('Content-Type: text/plain');

echo "=== FIXING REACTION ERROR ===\n\n";

// Load environment
require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\ORM\EntityManagerInterface;
use App\Kernel;

try {
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    
    $entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
    $connection = $entityManager->getConnection();
    
    echo "1. Checking database connection...\n";
    $connection->executeQuery('SELECT 1')->fetchOne();
    echo "✓ Database connection OK\n";
    
    echo "\n2. Checking reaction_publication table...\n";
    $tableExists = $connection->fetchOne("SHOW TABLES LIKE 'reaction_publication'");
    
    if (!$tableExists) {
        echo "✗ Table reaction_publication missing - creating...\n";
        
        $createTableSQL = "
        CREATE TABLE reaction_publication (
            id INT AUTO_INCREMENT PRIMARY KEY,
            publication_id INT NOT NULL,
            user_id INT NOT NULL,
            type VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_publication_user (publication_id, user_id),
            INDEX idx_publication (publication_id),
            INDEX idx_user (user_id),
            FOREIGN KEY (publication_id) REFERENCES publication(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_publication (user_id, publication_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $connection->executeStatement($createTableSQL);
        echo "✓ Table reaction_publication created\n";
    } else {
        echo "✓ Table reaction_publication exists\n";
        
        // Check table structure
        $columns = $connection->fetchAll("DESCRIBE reaction_publication");
        echo "Current structure:\n";
        foreach ($columns as $column) {
            echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    }
    
    echo "\n3. Checking publication table...\n";
    $publicationTableExists = $connection->fetchOne("SHOW TABLES LIKE 'publication'");
    if ($publicationTableExists) {
        echo "✓ Table publication exists\n";
        
        // Check if there are publications
        $count = $connection->fetchOne("SELECT COUNT(*) FROM publication");
        echo "  - Found " . $count . " publications\n";
    } else {
        echo "✗ Table publication missing\n";
    }
    
    echo "\n4. Checking users table...\n";
    $usersTableExists = $connection->fetchOne("SHOW TABLES LIKE 'users'");
    if ($usersTableExists) {
        echo "✓ Table users exists\n";
        
        // Check if there are users
        $count = $connection->fetchOne("SELECT COUNT(*) FROM users");
        echo "  - Found " . $count . " users\n";
    } else {
        echo "✗ Table users missing\n";
    }
    
    echo "\n5. Testing reaction functionality...\n";
    
    // Test if we can create a reaction
    try {
        $testPublicationId = $connection->fetchOne("SELECT id FROM publication LIMIT 1");
        $testUserId = $connection->fetchOne("SELECT id FROM users LIMIT 1");
        
        if ($testPublicationId && $testUserId) {
            echo "✓ Found test publication and user\n";
            
            // Test insert
            $connection->executeStatement(
                "INSERT IGNORE INTO reaction_publication (publication_id, user_id, type) VALUES (?, ?, ?)",
                [$testPublicationId, $testUserId, 'like']
            );
            
            echo "✓ Test reaction insert successful\n";
            
            // Clean up test data
            $connection->executeStatement(
                "DELETE FROM reaction_publication WHERE publication_id = ? AND user_id = ?",
                [$testPublicationId, $testUserId]
            );
            
            echo "✓ Test data cleaned up\n";
        } else {
            echo "✗ No test data available\n";
        }
    } catch (Exception $e) {
        echo "✗ Reaction test failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n6. Fixing JavaScript issues...\n";
    
    // Update the reaction JavaScript to handle errors better
    $reactionFile = __DIR__ . '/../templates/components/reaction_buttons.html.twig';
    if (file_exists($reactionFile)) {
        $content = file_get_contents($reactionFile);
        
        // Add better error handling and debugging
        if (strpos($content, 'console.error') === false) {
            $enhancedJS = "
        // Enhanced error handling
        .catch(error => {
            console.error('Reaction error details:', {
                url: '/reaction/add/' + publicationId,
                status: error.status,
                message: error.message,
                response: error.response
            });
            
            hideEnhancedLoading(button);
            
            // Show more specific error message
            let errorMessage = 'Erreur de connexion au serveur';
            if (error.status === 401) {
                errorMessage = 'Vous devez être connecté pour réagir';
            } else if (error.status === 403) {
                errorMessage = 'Accès refusé';
            } else if (error.status === 404) {
                errorMessage = 'Publication non trouvée';
            } else if (error.status === 500) {
                errorMessage = 'Erreur interne du serveur';
            }
            
            showEnhancedNotification(errorMessage, 'error');
        });";
            
            // Replace the existing catch block
            $content = preg_replace(
                '/\.catch\(error => \{[\s\S]*?\}\);/',
                $enhancedJS,
                $content
            );
            
            file_put_contents($reactionFile, $content);
            echo "✓ Enhanced JavaScript error handling\n";
        }
    }
    
    echo "\n=== FIX COMPLETED ===\n";
    echo "The reaction system should now work properly!\n\n";
    echo "Next steps:\n";
    echo "1. Make sure you're logged in\n";
    echo "2. Try clicking like/dislike buttons\n";
    echo "3. Check browser console for any remaining errors\n";
    echo "4. If still not working, check Symfony logs\n";
    
} catch (Exception $e) {
    echo "✗ Error during fix: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
