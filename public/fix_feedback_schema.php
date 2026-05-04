<?php

require __DIR__ . '/../vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

// Database connection parameters
$connectionParams = [
    'dbname' => 'esprit_pidev',
    'user' => 'root',
    'password' => '',
    'host' => 'localhost',
    'driver' => 'mysqli',
];

try {
    $conn = DriverManager::getConnection($connectionParams);
    
    echo "Checking feedback table structure...\n";
    
    // Check if priority column exists
    $sm = $conn->createSchemaManager();
    $columns = $sm->listTableColumns('feedback');
    
    $columnNames = array_keys($columns);
    echo "Current columns: " . implode(', ', $columnNames) . "\n";
    
    // Add missing columns
    $missingColumns = [];
    
    if (!in_array('priority', $columnNames)) {
        $missingColumns[] = 'priority';
    }
    if (!in_array('auto_response', $columnNames)) {
        $missingColumns[] = 'auto_response';
    }
    if (!in_array('auto_response_generated_at', $columnNames)) {
        $missingColumns[] = 'auto_response_generated_at';
    }
    if (!in_array('auto_response_sent_at', $columnNames)) {
        $missingColumns[] = 'auto_response_sent_at';
    }
    
    if (empty($missingColumns)) {
        echo "All required columns exist. No changes needed.\n";
    } else {
        echo "Missing columns: " . implode(', ', $missingColumns) . "\n";
        echo "Adding missing columns...\n";
        
        // Add priority column
        if (!in_array('priority', $columnNames)) {
            $conn->executeStatement("ALTER TABLE feedback ADD COLUMN priority VARCHAR(20) DEFAULT 'normal' AFTER status");
            echo "✓ Added priority column\n";
        }
        
        // Add auto_response column
        if (!in_array('auto_response', $columnNames)) {
            $conn->executeStatement("ALTER TABLE feedback ADD COLUMN auto_response TEXT NULL AFTER priority");
            echo "✓ Added auto_response column\n";
        }
        
        // Add auto_response_generated_at column
        if (!in_array('auto_response_generated_at', $columnNames)) {
            $conn->executeStatement("ALTER TABLE feedback ADD COLUMN auto_response_generated_at DATETIME NULL AFTER auto_response");
            echo "✓ Added auto_response_generated_at column\n";
        }
        
        // Add auto_response_sent_at column
        if (!in_array('auto_response_sent_at', $columnNames)) {
            $conn->executeStatement("ALTER TABLE feedback ADD COLUMN auto_response_sent_at DATETIME NULL AFTER auto_response_generated_at");
            echo "✓ Added auto_response_sent_at column\n";
        }
        
        echo "\n✅ All missing columns added successfully!\n";
    }
    
    // Verify the changes
    echo "\nVerifying updated table structure...\n";
    $columns = $sm->listTableColumns('feedback');
    $columnNames = array_keys($columns);
    echo "Updated columns: " . implode(', ', $columnNames) . "\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
