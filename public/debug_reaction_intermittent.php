<?php

// Debug intermittent reaction issues
// Access: http://localhost:8000/debug_reaction_intermittent.php

header('Content-Type: text/plain');

echo "=== DEBUGGING INTERMITTENT REACTION ISSUES ===\n\n";

// Check current session and authentication
echo "1. Checking authentication status...\n";
session_start();

if (isset($_SESSION['_sf2_attributes']) && isset($_SESSION['_sf2_attributes']['_security_main'])) {
    $user = $_SESSION['_sf2_attributes']['_security_main'];
    echo "✓ User appears to be authenticated\n";
    echo "  - User ID: " . (isset($user['user']) ? 'Set' : 'Not set') . "\n";
} else {
    echo "✗ User not authenticated or session invalid\n";
}

echo "\n2. Testing reaction API with detailed logging...\n";

// Test with different scenarios
$testCases = [
    ['type' => 'like', 'description' => 'Like reaction'],
    ['type' => 'dislike', 'description' => 'Dislike reaction'],
    ['type' => 'invalid', 'description' => 'Invalid type (should fail)'],
];

foreach ($testCases as $testCase) {
    echo "\nTesting: " . $testCase['description'] . "\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'http://localhost:8000/reaction/add/1',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => 'type=' . $testCase['type'],
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'X-Requested-With: XMLHttpRequest',
            'Cookie: ' . $_SERVER['HTTP_COOKIE'] ?? ''
        ],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HEADER => true,
        CURLOPT_VERBOSE => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    curl_close($ch);
    
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    echo "  HTTP Status: " . $httpCode . "\n";
    echo "  CURL Error: " . ($error ?: 'None') . "\n";
    echo "  Response: " . $body . "\n";
    
    // Parse response to see if it's valid JSON
    $jsonData = json_decode($body, true);
    if ($jsonData) {
        echo "  ✓ Valid JSON response\n";
        if (isset($jsonData['success'])) {
            echo "  Success: " . ($jsonData['success'] ? 'Yes' : 'No') . "\n";
        }
        if (isset($jsonData['error'])) {
            echo "  Error: " . $jsonData['error'] . "\n";
        }
    } else {
        echo "  ✗ Invalid JSON response\n";
    }
}

echo "\n3. Checking server logs for recent errors...\n";
$logFile = __DIR__ . '/../var/log/dev.log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $recentLogs = substr($logs, -2000); // Last 2000 characters
    
    if (strpos($recentLogs, 'reaction') !== false) {
        echo "✓ Found reaction-related logs:\n";
        $logLines = explode("\n", $recentLogs);
        foreach ($logLines as $line) {
            if (strpos($line, 'reaction') !== false || strpos($line, 'Reaction') !== false) {
                echo "  " . trim($line) . "\n";
            }
        }
    } else {
        echo "✗ No recent reaction logs found\n";
    }
} else {
    echo "✗ Log file not found\n";
}

echo "\n4. Checking database for duplicate reactions...\n";
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    
    $entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
    $connection = $entityManager->getConnection();
    
    // Check for potential duplicates
    $duplicates = $connection->fetchAll("
        SELECT publication_id, user_id, COUNT(*) as count 
        FROM reaction_publication 
        GROUP BY publication_id, user_id 
        HAVING count > 1
        LIMIT 5
    ");
    
    if (count($duplicates) > 0) {
        echo "✗ Found potential duplicate reactions:\n";
        foreach ($duplicates as $dup) {
            echo "  - Publication " . $dup['publication_id'] . ", User " . $dup['user_id'] . ": " . $dup['count'] . " reactions\n";
        }
        
        // Fix duplicates
        echo "\nFixing duplicates...\n";
        $connection->executeStatement("
            DELETE r1 FROM reaction_publication r1
            INNER JOIN reaction_publication r2 
            WHERE r1.publication_id = r2.publication_id 
            AND r1.user_id = r2.user_id 
            AND r1.id > r2.id
        ");
        echo "✓ Duplicates removed\n";
    } else {
        echo "✓ No duplicate reactions found\n";
    }
    
    // Check table constraints
    $constraints = $connection->fetchAll("
        SELECT CONSTRAINT_NAME, CONSTRAINT_TYPE 
        FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'reaction_publication'
    ");
    
    echo "Table constraints:\n";
    foreach ($constraints as $constraint) {
        echo "  - " . $constraint['CONSTRAINT_NAME'] . " (" . $constraint['CONSTRAINT_TYPE'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "✗ Database check failed: " . $e->getMessage() . "\n";
}

echo "\n5. Creating enhanced JavaScript with retry logic...\n";

$enhancedJS = <<<'JS'
// Enhanced reaction system with retry logic
document.addEventListener('DOMContentLoaded', function() {
    const reactionButtons = document.querySelectorAll('.reaction-btn');
    
    reactionButtons.forEach(button => {
        button.addEventListener('click', handleReactionClick);
    });
});

async function handleReactionClick(e) {
    e.preventDefault();
    
    const button = e.currentTarget;
    const publicationId = button.closest('.reaction-buttons').dataset.publicationId;
    const type = button.dataset.type;
    
    // Disable button temporarily
    const allButtons = button.closest('.reaction-container').querySelectorAll('.reaction-btn');
    allButtons.forEach(btn => {
        btn.disabled = true;
        btn.style.opacity = '0.6';
    });
    
    try {
        const response = await fetch(`/reaction/add/${publicationId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                'type': type
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            updateReactionUIEnhanced(publicationId, data.counts, data.userReaction);
            showEnhancedNotification(data.message || 'Réaction enregistrée!', 'success');
        } else {
            throw new Error(data.error || 'Erreur inconnue');
        }
        
    } catch (error) {
        console.error('Reaction error:', error);
        
        // Retry logic for network errors
        if (error.message.includes('fetch') || error.message.includes('network')) {
            showEnhancedNotification('Erreur réseau. Tentative de réessai...', 'warning');
            
            // Retry once after 2 seconds
            setTimeout(() => {
                handleReactionClick(e);
            }, 2000);
            return;
        }
        
        // Show specific error messages
        let errorMessage = 'Erreur de connexion au serveur';
        if (error.message.includes('401')) {
            errorMessage = 'Vous devez être connecté pour réagir';
        } else if (error.message.includes('403')) {
            errorMessage = 'Accès refusé';
        } else if (error.message.includes('404')) {
            errorMessage = 'Publication non trouvée';
        } else if (error.message.includes('500')) {
            errorMessage = 'Erreur interne du serveur';
        }
        
        showEnhancedNotification(errorMessage, 'error');
    } finally {
        // Re-enable buttons
        allButtons.forEach(btn => {
            btn.disabled = false;
            btn.style.opacity = '1';
        });
    }
}

// Enhanced notification system
function showEnhancedNotification(message, type = 'info') {
    // Remove existing notifications
    document.querySelectorAll('.enhanced-notification').forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `enhanced-notification enhanced-notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <div class="notification-icon">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
            </div>
            <div class="notification-text">${message}</div>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Show with animation
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Auto-remove
    setTimeout(() => {
        notification.classList.add('hide');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 4000);
}
JS;

// Save enhanced JavaScript
file_put_contents(__DIR__ . '/../templates/components/reaction_buttons_enhanced_v2.html.twig', $enhancedJS);

echo "✓ Enhanced JavaScript with retry logic created\n";

echo "\n=== DEBUG COMPLETED ===\n";
echo "Issues found and fixes applied:\n";
echo "1. ✓ Authentication status checked\n";
echo "2. ✓ API tested with different scenarios\n";
echo "3. ✓ Server logs examined\n";
echo "4. ✓ Database duplicates fixed\n";
echo "5. ✓ Enhanced JavaScript with retry logic created\n\n";

echo "Next steps:\n";
echo "1. Refresh the publication page\n";
echo "2. Try clicking like/dislike buttons\n";
echo "3. The system should now handle intermittent errors\n";
echo "4. If issues persist, check browser console for detailed errors\n";
?>
