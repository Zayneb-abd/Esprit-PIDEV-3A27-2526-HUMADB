<?php

// Comprehensive fix for all missing columns in users table
// Access this via: http://localhost:8000/comprehensive_fix.php

header('Content-Type: text/plain');

echo "=== COMPREHENSIVE DATABASE COLUMN FIX ===\n\n";

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
    
    // All columns that should exist based on User entity
    $requiredColumns = [
        'is_active' => "ALTER TABLE users ADD is_active TINYINT(1) DEFAULT 1 NOT NULL",
        'cv_filename' => "ALTER TABLE users ADD cv_filename VARCHAR(255) NULL",
        'updated_at' => "ALTER TABLE users ADD updated_at DATETIME NULL",
        'reset_token' => "ALTER TABLE users ADD reset_token VARCHAR(255) NULL",
        'token_expiry' => "ALTER TABLE users ADD token_expiry DATETIME NULL",
        'reputation_score' => "ALTER TABLE users ADD reputation_score INT DEFAULT 0 NOT NULL",
        'face_image' => "ALTER TABLE users ADD face_image VARCHAR(255) NULL",
        'date_naissance' => "ALTER TABLE users ADD date_naissance DATE NULL",
        'manager_id' => "ALTER TABLE users ADD manager_id INT NULL"
    ];
    
    echo "Checking and adding missing columns:\n";
    $addedColumns = [];
    
    foreach ($requiredColumns as $columnName => $alterSql) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE '$columnName'");
        $stmt->execute();
        $columnExists = $stmt->rowCount() > 0;
        
        if ($columnExists) {
            echo "  ✓ $columnName - already exists\n";
        } else {
            echo "  + $columnName - adding...\n";
            $pdo->exec($alterSql);
            $addedColumns[] = $columnName;
        }
    }
    
    if (!empty($addedColumns)) {
        echo "\n✓ Added columns: " . implode(', ', $addedColumns) . "\n";
        
        // Add foreign key for manager_id if it was added
        if (in_array('manager_id', $addedColumns)) {
            try {
                $pdo->exec("ALTER TABLE users ADD CONSTRAINT FK_users_manager FOREIGN KEY (manager_id) REFERENCES users (id)");
                echo "  ✓ Added foreign key constraint for manager_id\n";
            } catch (Exception $e) {
                echo "  ! Could not add foreign key (may already exist): " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "\n✓ All required columns already exist\n";
    }
    
    // Test a comprehensive query
    echo "\n=== TESTING COMPREHENSIVE QUERY ===\n";
    $testSql = "SELECT id, email, is_active, cv_filename, reputation_score, face_image FROM users LIMIT 3";
    $stmt = $pdo->prepare($testSql);
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "✓ Query successful. Found " . count($users) . " users:\n";
    foreach ($users as $user) {
        echo "  - ID: {$user['id']}, Email: {$user['email']}, Active: {$user['is_active']}, CV: " . ($user['cv_filename'] ?? 'NULL') . ", Rep: {$user['reputation_score']}\n";
    }
    
    echo "\n=== ALL FIXES COMPLETED ===\n";
    echo "The SQL errors for missing columns should now be resolved!\n";
    
} catch (PDOException $e) {
    echo "✗ Database Error: " . $e->getMessage() . "\n";
    echo "Please ensure MySQL is running and database 'humadb' exists.\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
