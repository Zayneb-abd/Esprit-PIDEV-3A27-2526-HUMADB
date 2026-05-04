-- =====================================================
-- FIX: Mettre à jour les mots de passe des employés
-- Mot de passe: password123
-- =====================================================

-- Hash BCrypt de "password123" (coût 13)
UPDATE users 
SET mdp = '$2y$13$nVnSGIL0jjYNBm8AU90hpevkOsavdzbfUZC44QGlKubkdXIP6d0cy'
WHERE email IN (
    'sami.benali@huma.tn',
    'fatma.karray@huma.tn', 
    'yassine.mejri@huma.tn',
    'leila.saidi@huma.tn',
    'karim.zidi@huma.tn'
);

-- Vérification
SELECT id, prenom, nom, email, role, manager_id
FROM users 
WHERE email LIKE '%@huma.tn' AND role = 'EMPLOYE';
