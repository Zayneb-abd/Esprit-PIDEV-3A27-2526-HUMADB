<?php

// Fix for participation table statut column
// Access: http://localhost:8000/fix_participation.php

header('Content-Type: text/plain');

echo "=== PARTICIPATION TABLE FIX ===\n\n";

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
    
    // Check if participation table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'participation'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "✗ participation table doesn't exist - creating it...\n";
        
        // Create participation table based on Participation entity
        $createTableSql = "CREATE TABLE participation (
            id INT AUTO_INCREMENT NOT NULL,
            date_inscription DATETIME NOT NULL,
            resultat VARCHAR(255) NULL,
            statut VARCHAR(20) NOT NULL DEFAULT 'en attente',
            employe_id INT DEFAULT NULL,
            formation_id INT DEFAULT NULL,
            INDEX IDX_participation_employe_id (employe_id),
            INDEX IDX_participation_formation_id (formation_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_participation_employe FOREIGN KEY (employe_id) REFERENCES users (id),
            CONSTRAINT FK_participation_formation FOREIGN KEY (formation_id) REFERENCES formation (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        
        $pdo->exec($createTableSql);
        echo "✓ participation table created with statut column\n";
        
    } else {
        echo "✓ participation table exists\n";
        
        // Check if statut column exists
        $stmt = $pdo->prepare("SHOW COLUMNS FROM participation LIKE 'statut'");
        $stmt->execute();
        $columnExists = $stmt->rowCount() > 0;
        
        if ($columnExists) {
            echo "✓ statut column already exists in participation table\n";
            
            // Show column details
            $stmt = $pdo->prepare("DESCRIBE participation statut");
            $stmt->execute();
            $columnInfo = $stmt->fetch();
            echo "  - Type: {$columnInfo['Type']}\n";
            echo "  - Default: {$columnInfo['Default']}\n";
            echo "  - Null: {$columnInfo['Null']}\n";
            
        } else {
            echo "✗ statut column missing - adding it now...\n";
            
            // Add the statut column
            $sql = "ALTER TABLE participation ADD statut VARCHAR(20) NOT NULL DEFAULT 'en attente'";
            $pdo->exec($sql);
            
            echo "✓ statut column added to participation table\n";
        }
        
        // Check other required columns
        $requiredColumns = [
            'date_inscription' => "ALTER TABLE participation ADD date_inscription DATETIME NOT NULL",
            'resultat' => "ALTER TABLE participation ADD resultat VARCHAR(255) NULL",
            'employe_id' => "ALTER TABLE participation ADD employe_id INT NULL",
            'formation_id' => "ALTER TABLE participation ADD formation_id INT NULL"
        ];
        
        echo "\nChecking other required columns:\n";
        foreach ($requiredColumns as $columnName => $alterSql) {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM participation LIKE '$columnName'");
            $stmt->execute();
            $columnExists = $stmt->rowCount() > 0;
            
            if ($columnExists) {
                echo "  ✓ $columnName - already exists\n";
            } else {
                echo "  + $columnName - adding...\n";
                $pdo->exec($alterSql);
            }
        }
    }
    
    // Test the query that was failing
    echo "\n=== TESTING THE FAILING QUERY ===\n";
    $testSql = "SELECT t0.id AS id_1, t0.date_inscription AS date_inscription_2, t0.resultat AS resultat_3, t0.statut AS statut_4, t0.employe_id AS employe_id_5, t0.formation_id AS formation_id_6 FROM participation t0 WHERE t0.formation_id = ?";
    $stmt = $pdo->prepare($testSql);
    $stmt->execute([1]);
    $participations = $stmt->fetchAll();
    
    echo "✓ Query successful. Found " . count($participations) . " participations for formation_id = 1\n";
    
    echo "\n=== FIX COMPLETED ===\n";
    echo "The participation table statut error should now be resolved!\n";
    
} catch (PDOException $e) {
    echo "✗ Database Error: " . $e->getMessage() . "\n";
    echo "Please ensure MySQL is running and database 'humadb' exists.\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
