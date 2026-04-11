<?php
require 'vendor/autoload.php';
$kernel = new App\Kernel('dev', true);
$kernel->boot();
$conn = $kernel->getContainer()->get('doctrine')->getConnection();
$stmt = $conn->executeQuery("DESCRIBE users");
$columns = $stmt->fetchAllAssociative();
foreach ($columns as $c) {
    if ($c['Null'] === 'NO' && $c['Default'] === null && $c['Extra'] !== 'auto_increment') {
        echo "MISSING DEFAULT: " . $c['Field'] . "\n";
    }
}
echo "Done.\n";
