-- =====================================================
-- VÉRIFICATION: Équipe de Nermine Jridi (Manager ID: 10)
-- =====================================================

-- 1. Voir tous les employés de l'équipe de Nermine
SELECT u.id, u.nom, u.prenom, u.email, u.manager_id,
       m.prenom AS manager_prenom, m.nom AS manager_nom
FROM users u
JOIN users m ON u.manager_id = m.id
WHERE u.manager_id = 10 
AND u.role = 'EMPLOYE';

-- 2. Compter le nombre d'employés dans l'équipe
SELECT COUNT(*) AS total_employes_equipe_nermine
FROM users 
WHERE manager_id = 10 
AND role = 'EMPLOYE';

-- 3. Voir les détails du manager Nermine
SELECT id, nom, prenom, email, role
FROM users 
WHERE id = 10;
