<?php

// Script to check and fix the missing is_active column
// Access this via: http://localhost:8000/check_and_fix_is_active.php

require_once __DIR__ . '/vendor/autoload.php';

use App\Kernel;

header('Content-Type: text/plain');

echo "=== IS_ACTIVE COLUMN FIX ===\n\n";

try {
    // Bootstrap the Symfony kernel
    $kernel = new Kernel('dev', true);
    $kernel->boot();
    
    // Get the Doctrine connection
    $connection = $kernel->getContainer()->get('doctrine.dbal.default_connection');
    
    echo "✓ Database connection established\n";
    
    // Check if users table exists
    $schemaManager = $connection->createSchemaManager();
    $tables = $schemaManager->listTableNames();
    
    if (!in_array('users', $tables)) {
        echo "✗ Users table not found!\n";
        exit(1);
    }
    
    echo "✓ Users table found\n";
    
    // Check if is_active column exists
    $table = $schemaManager->listTableDetails('users');
    
    if ($table->hasColumn('is_active')) {
        echo "✓ Column 'is_active' already exists\n";
        
        // Show column details
        $column = $table->getColumn('is_active');
        echo "  - Type: " . $column->getType()->getName() . "\n";
        echo "  - Default: " . ($column->getDefault() ?? 'NULL') . "\n";
        echo "  - Not null: " . ($column->getNotnull() ? 'Yes' : 'No') . "\n";
        
    } else {
        echo "✗ Column 'is_active' missing - adding it now...\n";
        
        // Add the column
        $sql = 'ALTER TABLE users ADD is_active TINYINT(1) DEFAULT 1 NOT NULL';
        $connection->executeStatement($sql);
        
        echo "✓ Column 'is_active' added successfully\n";
        
        // Update existing records
        $updateSql = 'UPDATE users SET is_active = 1 WHERE is_active IS NULL';
        $connection->executeStatement($updateSql);
        
        echo "✓ Existing records updated\n";
    }
    
    // Test a simple query to verify the fix
    echo "\n=== TESTING QUERY ===\n";
    $testSql = 'SELECT id, email, is_active FROM users LIMIT 5';
    $result = $connection->fetchAllAssociative($testSql);
    
    echo "✓ Test query successful. Found " . count($result) . " users:\n";
    foreach ($result as $row) {
        echo "  - ID: {$row['id']}, Email: {$row['email']}, Active: {$row['is_active']}\n";
    }
    
    echo "\n=== FIX COMPLETED ===\n";
    echo "The SQL error should now be resolved!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
