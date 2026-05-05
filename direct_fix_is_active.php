<?php

// Direct database connection to fix is_active column
// Based on .env: DATABASE_URL="mysql://root:@127.0.0.1:3306/humadb"

header('Content-Type: text/plain');

echo "=== DIRECT DATABASE FIX FOR IS_ACTIVE COLUMN ===\n\n";

try {
    // Parse DATABASE_URL
    $dbUrl = "mysql://root:@127.0.0.1:3306/humadb";
    $parsed = parse_url($dbUrl);
    
    $host = $parsed['host'];
    $port = $parsed['port'] ?? 3306;
    $database = ltrim($parsed['path'], '/');
    $username = $parsed['user'] ?? 'root';
    $password = $parsed['pass'] ?? '';
    
    echo "Connecting to database:\n";
    echo "  - Host: $host:$port\n";
    echo "  - Database: $database\n";
    echo "  - Username: $username\n\n";
    
    // Connect directly with PDO
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✓ Database connection successful\n\n";
    
    // Check if is_active column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'is_active'");
    $stmt->execute();
    $columnExists = $stmt->rowCount() > 0;
    
    if ($columnExists) {
        echo "✓ Column 'is_active' already exists\n";
        
        // Show column details
        $stmt = $pdo->prepare("DESCRIBE users is_active");
        $stmt->execute();
        $columnInfo = $stmt->fetch();
        echo "  - Type: {$columnInfo['Type']}\n";
        echo "  - Default: {$columnInfo['Default']}\n";
        echo "  - Null: {$columnInfo['Null']}\n";
        
    } else {
        echo "✗ Column 'is_active' missing - adding it now...\n";
        
        // Add the column
        $sql = "ALTER TABLE users ADD is_active TINYINT(1) DEFAULT 1 NOT NULL";
        $pdo->exec($sql);
        
        echo "✓ Column 'is_active' added successfully\n";
        
        // Update existing records to ensure they have a value
        $updateSql = "UPDATE users SET is_active = 1 WHERE is_active IS NULL";
        $pdo->exec($updateSql);
        
        echo "✓ Existing records updated with default value\n";
    }
    
    // Test the column with a query
    echo "\n=== TESTING QUERY ===\n";
    $testSql = "SELECT id, email, is_active FROM users LIMIT 5";
    $stmt = $pdo->prepare($testSql);
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "✓ Query successful. Found " . count($users) . " users:\n";
    foreach ($users as $user) {
        echo "  - ID: {$user['id']}, Email: {$user['email']}, Active: {$user['is_active']}\n";
    }
    
    echo "\n=== FIX COMPLETED SUCCESSFULLY ===\n";
    echo "The SQL error 'Unknown column t0.is_active' should now be resolved!\n";
    
} catch (PDOException $e) {
    echo "✗ Database Error: " . $e->getMessage() . "\n";
    echo "Please check:\n";
    echo "  - MySQL server is running\n";
    echo "  - Database 'humadb' exists\n";
    echo "  - User 'root' has proper permissions\n";
} catch (Exception $e) {
    echo "✗ General Error: " . $e->getMessage() . "\n";
}
?>
