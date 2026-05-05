<?php

// Test reaction API endpoint
// Access: http://localhost:8000/test_reaction_api.php

header('Content-Type: text/plain');

echo "=== REACTION API TEST ===\n\n";

// Test 1: Check if route exists
echo "1. Testing route availability...\n";
$testUrl = 'http://localhost:8000/reaction/add/1';

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
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: " . $httpCode . "\n";
echo "CURL Error: " . ($error ?: 'None') . "\n";
echo "Response: " . $response . "\n\n";

// Test 2: Check if ReactionPublication entity exists
echo "2. Checking ReactionPublication entity...\n";
$entityFile = __DIR__ . '/../src/Entity/ReactionPublication.php';
if (file_exists($entityFile)) {
    echo "✓ ReactionPublication entity exists\n";
} else {
    echo "✗ ReactionPublication entity missing\n";
}

// Test 3: Check if repository exists
echo "\n3. Checking ReactionPublicationRepository...\n";
$repositoryFile = __DIR__ . '/../src/Repository/ReactionPublicationRepository.php';
if (file_exists($repositoryFile)) {
    echo "✓ ReactionPublicationRepository exists\n";
} else {
    echo "✗ ReactionPublicationRepository missing\n";
}

// Test 4: Check database connection
echo "\n4. Testing database connection...\n";
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    // Load Symfony environment
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    
    $entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
    $connection = $entityManager->getConnection();
    
    // Test if reaction_publication table exists
    $tableExists = $connection->fetchOne("SHOW TABLES LIKE 'reaction_publication'");
    if ($tableExists) {
        echo "✓ reaction_publication table exists\n";
        
        // Check table structure
        $columns = $connection->fetchAll("DESCRIBE reaction_publication");
        echo "Table structure:\n";
        foreach ($columns as $column) {
            echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    } else {
        echo "✗ reaction_publication table missing\n";
        echo "Creating table...\n";
        
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS reaction_publication (
            id INT AUTO_INCREMENT PRIMARY KEY,
            publication_id INT NOT NULL,
            user_id INT NOT NULL,
            type VARCHAR(10) NOT NULL,
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
        echo "✓ Table created successfully\n";
    }
    
} catch (Exception $e) {
    echo "✗ Database connection error: " . $e->getMessage() . "\n";
}

// Test 5: Check if user is authenticated
echo "\n5. Authentication check...\n";
echo "Note: You need to be logged in to test reactions\n";
echo "Please log in and then visit a publication page\n";

echo "\n=== TEST COMPLETED ===\n";
echo "If you see 'Erreur de connexion au serveur', the issue could be:\n";
echo "1. User not logged in\n";
echo "2. CSRF token missing\n";
echo "3. Route not found\n";
echo "4. Database connection issue\n";
echo "5. Missing entity/repository\n\n";

echo "Fixes to apply:\n";
echo "1. Make sure you're logged in\n";
echo "2. Check if reaction_publication table exists\n";
echo "3. Verify CSRF token in AJAX request\n";
echo "4. Check Symfony logs for errors\n";
?>
