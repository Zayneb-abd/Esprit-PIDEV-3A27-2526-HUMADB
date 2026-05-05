-- =====================================================
-- TABLE: planning_rules (pour PlanningBundle)
-- À exécuter dans phpMyAdmin
-- =====================================================

CREATE TABLE IF NOT EXISTS planning_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    manager_id INT DEFAULT NULL,
    max_simultaneous_absences INT NOT NULL DEFAULT 2,
    max_absence_percentage DECIMAL(5,2) NOT NULL DEFAULT 30.00,
    require_minimum_coverage TINYINT(1) NOT NULL DEFAULT 1,
    blocked_dates JSON DEFAULT NULL,
    created_at DATETIME NOT NULL,
    INDEX IDX_planning_manager (manager_id),
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default rule for Nermine (manager_id = 10)
INSERT INTO planning_rules (manager_id, max_simultaneous_absences, max_absence_percentage, require_minimum_coverage, created_at)
VALUES (10, 2, 30.00, 1, NOW());
