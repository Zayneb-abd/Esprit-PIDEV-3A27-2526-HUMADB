<?php

require_once __DIR__ . '/vendor/autoload_runtime.php';

use App\Kernel;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Migrator;
use Doctrine\Migrations\Query\Query;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

// Bootstrap the Symfony kernel
$kernel = new Kernel($_ENV['APP_ENV'] ?? 'dev', (bool)($_ENV['APP_DEBUG'] ?? true));
$kernel->boot();

// Get the Doctrine entity manager
$entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
$connection = $entityManager->getConnection();

// Run the migration SQL directly
try {
    echo "Running migration: Add is_active column to users table...\n";
    
    // Check if column already exists
    $schemaManager = $connection->createSchemaManager();
    $table = $schemaManager->listTableDetails('users');
    
    if ($table->hasColumn('is_active')) {
        echo "Column 'is_active' already exists in users table.\n";
    } else {
        // Add the column
        $connection->executeStatement('ALTER TABLE users ADD is_active TINYINT(1) DEFAULT 1 NOT NULL');
        echo "Successfully added 'is_active' column to users table.\n";
    }
    
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
