<?php
require 'vendor/autoload.php';
$kernel = new App\Kernel('dev', true);
$kernel->boot();
$conn = $kernel->getContainer()->get('doctrine')->getConnection();

try {
    $conn->executeStatement("CREATE TABLE IF NOT EXISTS action_logs (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_866E7D52A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`");
    echo "action_logs table checked/created.\n";
} catch (\Exception $e) {
    echo "action_logs table note: " . $e->getMessage() . "\n";
}

try {
    $conn->executeStatement("ALTER TABLE users ADD reputation_score INT DEFAULT 0 NOT NULL");
    echo "users.reputation_score added.\n";
} catch (\Exception $e) {
    echo "users.reputation_score note: " . $e->getMessage() . "\n";
}

try {
    $conn->executeStatement("ALTER TABLE users ADD face_image LONGBLOB DEFAULT NULL");
    echo "users.face_image added.\n";
} catch (\Exception $e) {
    echo "users.face_image note: " . $e->getMessage() . "\n";
}

try {
    $conn->executeStatement("ALTER TABLE users ADD reset_token VARCHAR(255) DEFAULT NULL");
    echo "users.reset_token added.\n";
} catch (\Exception $e) {
    echo "users.reset_token note: " . $e->getMessage() . "\n";
}

try {
    $conn->executeStatement("ALTER TABLE users ADD token_expiry DATETIME DEFAULT NULL");
    echo "users.token_expiry added.\n";
} catch (\Exception $e) {
    echo "users.token_expiry note: " . $e->getMessage() . "\n";
}
