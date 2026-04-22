-- Création de la table jours_feries
CREATE TABLE IF NOT EXISTS jours_feries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    pays VARCHAR(10) DEFAULT 'TN',
    type VARCHAR(50) DEFAULT 'fixe',
    annee INT NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_annee (annee),
    INDEX idx_pays (pays),
    UNIQUE KEY unique_date_pays (date, pays)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des jours fériés fixes de la Tunisie pour 2025
INSERT INTO jours_feries (nom, date, pays, type, annee) VALUES
('Jour de l\'An', '2025-01-01', 'TN', 'fixe', 2025),
('Fête de la Révolution', '2025-01-14', 'TN', 'fixe', 2025),
('Fête de la Jeunesse', '2025-03-20', 'TN', 'fixe', 2025),
('Journée des Martyrs', '2025-04-09', 'TN', 'fixe', 2025),
('Fête du Travail', '2025-05-01', 'TN', 'fixe', 2025),
('Fête de la République', '2025-07-25', 'TN', 'fixe', 2025),
('Journée de la Femme', '2025-08-13', 'TN', 'fixe', 2025),
('Journée de l\'Évacuation', '2025-10-15', 'TN', 'fixe', 2025),
('Fête de l\'Indépendance', '2025-03-20', 'TN', 'fixe', 2025);

-- Afficher les jours fériés insérés
SELECT * FROM jours_feries WHERE annee = 2025 ORDER BY date;
