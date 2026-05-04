<?php

// Simple script to add the is_active column to the users table
// This should be accessed via web browser

require_once __DIR__ . '/../vendor/autoload.php';

use App\Kernel;

// Bootstrap the Symfony kernel
$kernel = new Kernel('dev', true);
$kernel->boot();

// Get the Doctrine connection
$connection = $kernel->getContainer()->get('doctrine.dbal.default_connection');

header('Content-Type: text/plain');

echo "Attempting to add 'is_active' column to users table...\n\n";

try {
    // Check if column already exists
    $schemaManager = $connection->createSchemaManager();
    $table = $schemaManager->listTableDetails('users');
    
    if ($table->hasColumn('is_active')) {
        echo "✓ Column 'is_active' already exists in users table.\n";
    } else {
        // Add the column
        $sql = 'ALTER TABLE users ADD is_active TINYINT(1) DEFAULT 1 NOT NULL';
        $connection->executeStatement($sql);
        echo "✓ Successfully added 'is_active' column to users table.\n";
    }
    
    echo "\nMigration completed successfully!\n";
    echo "The SQL error should now be resolved.\n";
    
} catch (Exception $e) {
    echo "✗ Error during migration: " . $e->getMessage() . "\n";
    echo "Please check your database connection and permissions.\n";
}
