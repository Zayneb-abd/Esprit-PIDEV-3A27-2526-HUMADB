<?php

// Debug 500 error on reaction/add endpoint
// Access: http://localhost:8000/debug_500_error.php

header('Content-Type: text/plain');

echo "=== DEBUGGING 500 ERROR ON /reaction/add/24 ===\n\n";

// Load Symfony environment
require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\ORM\EntityManagerInterface;
use App\Kernel;

try {
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    
    $entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
    $connection = $entityManager->getConnection();
    
    echo "1. Checking publication ID 24 exists...\n";
    $publicationExists = $connection->fetchOne("SELECT COUNT(*) FROM publication WHERE id = 24");
    echo "  Publication 24 exists: " . ($publicationExists ? 'Yes' : 'No') . "\n";
    
    if (!$publicationExists) {
        echo "  Creating test publication...\n";
        $connection->executeStatement("
            INSERT INTO publication (titre, contenu, datePublication) 
            VALUES ('Test Publication', 'Test content for debugging', NOW())
        ");
        $newId = $connection->lastInsertId();
        echo "  Created publication with ID: " . $newId . "\n";
    }
    
    echo "\n2. Checking current user authentication...\n";
    
    // Check if user is logged in
    $session = $kernel->getContainer()->get('session');
    $token = $kernel->getContainer()->get('security.token_storage')->getToken();
    
    if ($token && $token->getUser()) {
        $user = $token->getUser();
        echo "  User authenticated: " . $user->getEmail() . "\n";
        echo "  User ID: " . $user->getId() . "\n";
        echo "  User roles: " . implode(', ', $user->getRoles()) . "\n";
    } else {
        echo "  ✗ No user authenticated\n";
        echo "  This is likely the cause of the 500 error\n";
    }
    
    echo "\n3. Testing ReactionController directly...\n";
    
    try {
        $controller = new \App\Controller\ReactionController(
            $entityManager->getRepository(\App\Entity\ReactionPublication::class),
            $entityManager
        );
        
        // Create mock request
        $request = new \Symfony\Component\HttpFoundation\Request();
        $request->request->set('type', 'like');
        
        // Test with publication ID 24
        $publication = $entityManager->getRepository(\App\Entity\Publication::class)->find(24);
        if (!$publication) {
            echo "  ✗ Publication 24 not found\n";
            $publication = $entityManager->getRepository(\App\Entity\Publication::class)->find(1);
            if ($publication) {
                echo "  Using publication ID 1 instead\n";
            }
        }
        
        if ($publication && $token && $token->getUser()) {
            echo "  Testing addReaction method...\n";
            
            try {
                $response = $controller->addReaction($publication->getId(), $request);
                echo "  ✓ addReaction method successful\n";
                echo "  Response status: " . $response->getStatusCode() . "\n";
                echo "  Response content: " . $response->getContent() . "\n";
            } catch (\Exception $e) {
                echo "  ✗ addReaction method failed: " . $e->getMessage() . "\n";
                echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
                echo "  Stack trace:\n" . $e->getTraceAsString() . "\n";
            }
        } else {
            echo "  ✗ Cannot test - missing publication or user\n";
        }
        
    } catch (\Exception $e) {
        echo "  ✗ Controller initialization failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n4. Checking database tables and constraints...\n";
    
    // Check reaction_publication table
    $tableExists = $connection->fetchOne("SHOW TABLES LIKE 'reaction_publication'");
    if ($tableExists) {
        echo "  ✓ reaction_publication table exists\n";
        
        // Check table structure
        $columns = $connection->fetchAll("DESCRIBE reaction_publication");
        echo "  Table structure:\n";
        foreach ($columns as $column) {
            echo "    - " . $column['Field'] . " (" . $column['Type'] . ") " . ($column['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
        }
        
        // Check foreign key constraints
        $constraints = $connection->fetchAll("
            SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'reaction_publication'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        if (count($constraints) > 0) {
            echo "  Foreign key constraints:\n";
            foreach ($constraints as $constraint) {
                echo "    - " . $constraint['CONSTRAINT_NAME'] . ": " . 
                     $constraint['TABLE_NAME'] . "." . $constraint['COLUMN_NAME'] . 
                     " -> " . $constraint['REFERENCED_TABLE_NAME'] . "." . $constraint['REFERENCED_COLUMN_NAME'] . "\n";
            }
        } else {
            echo "  ✗ No foreign key constraints found\n";
        }
    } else {
        echo "  ✗ reaction_publication table missing\n";
    }
    
    echo "\n5. Testing database operations...\n";
    
    try {
        // Test if we can select from publication table
        $publications = $connection->fetchAll("SELECT id, titre FROM publication LIMIT 3");
        echo "  ✓ Can read from publication table\n";
        echo "  Available publications:\n";
        foreach ($publications as $pub) {
            echo "    - ID " . $pub['id'] . ": " . $pub['titre'] . "\n";
        }
        
        // Test if we can select from users table
        $users = $connection->fetchAll("SELECT id, email FROM users LIMIT 3");
        echo "  ✓ Can read from users table\n";
        echo "  Available users:\n";
        foreach ($users as $user) {
            echo "    - ID " . $user['id'] . ": " . $user['email'] . "\n";
        }
        
        // Test reaction insert
        if (isset($publication) && isset($user)) {
            echo "  Testing reaction insert...\n";
            
            $connection->executeStatement("
                INSERT IGNORE INTO reaction_publication (publication_id, user_id, type, created_at) 
                VALUES (?, ?, ?, NOW())
            ", [$publication->getId(), $user->getId(), 'like']);
            
            echo "  ✓ Reaction insert successful\n";
            
            // Clean up
            $connection->executeStatement("
                DELETE FROM reaction_publication 
                WHERE publication_id = ? AND user_id = ?
            ", [$publication->getId(), $user->getId()]);
            
            echo "  ✓ Test data cleaned up\n";
        }
        
    } catch (\Exception $e) {
        echo "  ✗ Database operation failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n6. Checking Symfony logs for recent errors...\n";
    
    $logFile = __DIR__ . '/../var/log/dev.log';
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        $recentLogs = substr($logs, -3000); // Last 3000 characters
        
        // Look for reaction-related errors
        $logLines = explode("\n", $recentLogs);
        $reactionErrors = [];
        
        foreach ($logLines as $line) {
            if (strpos($line, 'reaction') !== false || 
                strpos($line, 'Reaction') !== false || 
                strpos($line, '500') !== false ||
                strpos($line, 'ERROR') !== false) {
                $reactionErrors[] = trim($line);
            }
        }
        
        if (count($reactionErrors) > 0) {
            echo "  Recent reaction-related errors:\n";
            foreach (array_slice($reactionErrors, -10) as $error) {
                echo "    " . $error . "\n";
            }
        } else {
            echo "  ✓ No recent reaction errors in logs\n";
        }
    } else {
        echo "  ✗ Log file not found\n";
    }
    
    echo "\n7. Creating fix for common issues...\n";
    
    // Fix 1: Ensure user is authenticated
    if (!$token || !$token->getUser()) {
        echo "  Fix: User authentication issue detected\n";
        echo "  Solution: Make sure you are logged in before testing reactions\n";
    }
    
    // Fix 2: Ensure publication exists
    if (!$publicationExists) {
        echo "  Fix: Publication 24 doesn't exist\n";
        echo "  Solution: Use a valid publication ID or create test publications\n";
    }
    
    // Fix 3: Check for missing foreign keys
    $fkCheck = $connection->fetchOne("
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'reaction_publication'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    if ($fkCheck < 2) {
        echo "  Fix: Missing foreign key constraints\n";
        echo "  Adding foreign key constraints...\n";
        
        try {
            // Drop existing constraints if any
            $connection->executeStatement("ALTER TABLE reaction_publication DROP FOREIGN KEY IF EXISTS FK_reaction_publication_publication_id");
            $connection->executeStatement("ALTER TABLE reaction_publication DROP FOREIGN KEY IF EXISTS FK_reaction_publication_user_id");
            
            // Add new constraints
            $connection->executeStatement("
                ALTER TABLE reaction_publication 
                ADD CONSTRAINT FK_reaction_publication_publication_id 
                FOREIGN KEY (publication_id) REFERENCES publication(id) ON DELETE CASCADE
            ");
            
            $connection->executeStatement("
                ALTER TABLE reaction_publication 
                ADD CONSTRAINT FK_reaction_publication_user_id 
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ");
            
            echo "  ✓ Foreign key constraints added\n";
        } catch (\Exception $e) {
            echo "  ✗ Failed to add constraints: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== DEBUG COMPLETED ===\n";
    echo "Most likely causes of 500 error:\n";
    echo "1. User not authenticated\n";
    echo "2. Publication ID doesn't exist\n";
    echo "3. Database constraint issues\n";
    echo "4. Repository method errors\n\n";
    
    echo "Next steps:\n";
    echo "1. Make sure you are logged in\n";
    echo "2. Use a valid publication ID\n";
    echo "3. Check browser console for detailed error messages\n";
    echo "4. Test with the debug output above\n";
    
} catch (\Exception $e) {
    echo "✗ Debug failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
