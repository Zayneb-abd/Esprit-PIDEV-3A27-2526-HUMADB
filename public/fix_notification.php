<?php

// Fix for notification table
// Access: http://localhost:8000/fix_notification.php

header('Content-Type: text/plain');

echo "=== NOTIFICATION TABLE FIX ===\n\n";

// Database connection from .env
$dbUrl = "mysql://root:@127.0.0.1:3306/humadb";
$parsed = parse_url($dbUrl);

$host = $parsed['host'];
$port = $parsed['port'] ?? 3306;
$database = ltrim($parsed['path'], '/');
$username = $parsed['user'] ?? 'root';
$password = $parsed['pass'] ?? '';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✓ Database connected: $database\n\n";
    
    // Check if notification table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'notification'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "✗ notification table doesn't exist - creating it...\n";
        
        // Create notification table based on Notification entity
        $createTableSql = "CREATE TABLE notification (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            reference_link VARCHAR(255) NULL,
            is_read TINYINT(1) DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_notification_user_id (user_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_notification_user FOREIGN KEY (user_id) REFERENCES users (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        
        $pdo->exec($createTableSql);
        echo "✓ notification table created successfully\n";
        
    } else {
        echo "✓ notification table already exists\n";
        
        // Check all required columns
        $requiredColumns = [
            'user_id' => "ALTER TABLE notification ADD user_id INT NOT NULL",
            'title' => "ALTER TABLE notification ADD title VARCHAR(255) NOT NULL",
            'message' => "ALTER TABLE notification ADD message TEXT NOT NULL",
            'reference_link' => "ALTER TABLE notification ADD reference_link VARCHAR(255) NULL",
            'is_read' => "ALTER TABLE notification ADD is_read TINYINT(1) DEFAULT 0 NOT NULL",
            'created_at' => "ALTER TABLE notification ADD created_at DATETIME NOT NULL"
        ];
        
        echo "Checking required columns:\n";
        foreach ($requiredColumns as $columnName => $alterSql) {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM notification LIKE '$columnName'");
            $stmt->execute();
            $columnExists = $stmt->rowCount() > 0;
            
            if ($columnExists) {
                echo "  ✓ $columnName - already exists\n";
            } else {
                echo "  + $columnName - adding...\n";
                $pdo->exec($alterSql);
                
                // Add foreign key for user_id if it was added
                if ($columnName === 'user_id') {
                    try {
                        $pdo->exec("ALTER TABLE notification ADD CONSTRAINT FK_notification_user FOREIGN KEY (user_id) REFERENCES users (id)");
                        echo "    ✓ Added foreign key constraint\n";
                    } catch (Exception $e) {
                        echo "    ! Could not add foreign key (may already exist)\n";
                    }
                }
            }
        }
    }
    
    // Test the query that was failing
    echo "\n=== TESTING THE FAILING QUERY ===\n";
    $testSql = "SELECT n0_.id AS id_0, n0_.title AS title_1, n0_.message AS message_2, n0_.reference_link AS reference_link_3, n0_.is_read AS is_read_4, n0_.created_at AS created_at_5, n0_.user_id AS user_id_6 FROM notification n0_ WHERE n0_.user_id = ? AND n0_.is_read = ? ORDER BY n0_.created_at DESC";
    $stmt = $pdo->prepare($testSql);
    $stmt->execute([2, false]);
    $notifications = $stmt->fetchAll();
    
    echo "✓ Query successful. Found " . count($notifications) . " unread notifications for user_id = 2\n";
    
    echo "\n=== FIX COMPLETED ===\n";
    echo "The notification table error should now be resolved!\n";
    
} catch (PDOException $e) {
    echo "✗ Database Error: " . $e->getMessage() . "\n";
    echo "Please ensure MySQL is running and database 'humadb' exists.\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
