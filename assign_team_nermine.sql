-- =====================================================
-- SCRIPT: Créer 5 employés + Affecter 7 employés à Nermine Jridi
-- Manager ID: 10 (nermine@huma.tn)
-- À exécuter dans phpMyAdmin (onglet SQL)
-- =====================================================

-- ÉTAPE 1: Créer 5 nouveaux employés
INSERT INTO users (nom, prenom, email, mdp, role) VALUES
('Ben Ali', 'Sami', 'sami.benali@huma.tn', '$2y$13$vXm8p50sQ7KQh7qLTa3NHO4eYJnYvKqJ5fPvXL2jR/7J5R8J6T9U2', 'EMPLOYE'),
('Karray', 'Fatma', 'fatma.karray@huma.tn', '$2y$13$vXm8p50sQ7KQh7qLTa3NHO4eYJnYvKqJ5fPvXL2jR/7J5R8J6T9U2', 'EMPLOYE'),
('Mejri', 'Yassine', 'yassine.mejri@huma.tn', '$2y$13$vXm8p50sQ7KQh7qLTa3NHO4eYJnYvKqJ5fPvXL2jR/7J5R8J6T9U2', 'EMPLOYE'),
('Saidi', 'Leila', 'leila.saidi@huma.tn', '$2y$13$vXm8p50sQ7KQh7qLTa3NHO4eYJnYvKqJ5fPvXL2jR/7J5R8J6T9U2', 'EMPLOYE'),
('Zidi', 'Karim', 'karim.zidi@huma.tn', '$2y$13$vXm8p50sQ7KQh7qLTa3NHO4eYJnYvKqJ5fPvXL2jR/7J5R8J6T9U2', 'EMPLOYE');

-- ÉTAPE 2: Affecter les 2 employés existants (ID 12 et 16) + les 5 nouveaux à Nermine
-- Note: Les nouveaux employés auront les IDs 20, 21, 22, 23, 24 (après les 19 existants)
-- Vérifie les IDs après l'INSERT ci-dessus

-- D'abord, voir les IDs des nouveaux employés créés
SELECT id, nom, prenom, email 
FROM users 
WHERE email IN ('sami.benali@huma.tn', 'fatma.karray@huma.tn', 'yassine.mejri@huma.tn', 
                'leila.saidi@huma.tn', 'karim.zidi@huma.tn');

-- ÉTAPE 3: Affecter TOUS les employés sans manager à Nermine (ID: 10)
-- Cela inclut les 2 existants (12, 16) + les 5 nouveaux
UPDATE users 
SET manager_id = 10 
WHERE role = 'EMPLOYE' 
AND manager_id IS NULL;

-- ÉTAPE 4: Vérification - Voir l'équipe complète de Nermine
SELECT u.id, u.nom, u.prenom, u.email, u.role, u.manager_id,
       m.prenom AS manager_prenom, m.nom AS manager_nom
FROM users u
LEFT JOIN users m ON u.manager_id = m.id
WHERE u.manager_id = 10;

-- ÉTAPE 5: Compter le nombre d'employés dans l'équipe
SELECT COUNT(*) AS nb_employes_equipe_nermine 
FROM users 
WHERE manager_id = 10 AND role = 'EMPLOYE';
