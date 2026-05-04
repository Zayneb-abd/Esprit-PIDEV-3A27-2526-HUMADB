<?php
// Ajouter les jours fériés pour 2026

$host = '127.0.0.1';
$dbname = 'humadb';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Jours fériés fixes pour 2026
    $joursFeries = [
        ['Jour de l\'An', '2026-01-01', 'fixe'],
        ['Fête de la Révolution', '2026-01-14', 'fixe'],
        ['Fête de la Jeunesse', '2026-03-20', 'fixe'],
        ['Journée des Martyrs', '2026-04-09', 'fixe'],
        ['Fête du Travail', '2026-05-01', 'fixe'],
        ['Fête de la République', '2026-07-25', 'fixe'],
        ['Journée de la Femme', '2026-08-13', 'fixe'],
        ['Journée de l\'Évacuation', '2026-10-15', 'fixe'],
    ];

    $insertSql = "INSERT INTO jours_feries (nom, date, pays, type, annee) 
                  VALUES (:nom, :date, 'TN', :type, 2026)
                  ON DUPLICATE KEY UPDATE nom = :nom2";
    $stmt = $pdo->prepare($insertSql);

    $count = 0;
    foreach ($joursFeries as $jour) {
        $stmt->execute([
            ':nom' => $jour[0],
            ':date' => $jour[1],
            ':type' => $jour[2],
            ':nom2' => $jour[0]
        ]);
        $count++;
    }

    echo "✅ $count jours fériés ajoutés pour 2026 !\n";
    
    // Vérifier les jours fériés d'avril 2026
    $check = $pdo->query("SELECT * FROM jours_feries WHERE date LIKE '2026-04-%'");
    $avril = $check->fetchAll(PDO::FETCH_ASSOC);
    echo "\n📅 Jours fériés d'avril 2026 :\n";
    foreach ($avril as $j) {
        echo "  - " . $j['date'] . " : " . $j['nom'] . "\n";
    }

} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
