-- =====================================================
-- TABLE: approval_history (pour WorkflowBundle)
-- À exécuter dans phpMyAdmin
-- =====================================================

CREATE TABLE IF NOT EXISTS approval_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conge_id INT DEFAULT NULL,
    absence_id INT DEFAULT NULL,
    approver_id INT NOT NULL,
    action VARCHAR(20) NOT NULL, -- 'approve' or 'reject'
    comment TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    INDEX IDX_history_conge (conge_id),
    INDEX IDX_history_absence (absence_id),
    INDEX IDX_history_approver (approver_id),
    INDEX IDX_history_date (created_at),
    FOREIGN KEY (conge_id) REFERENCES conge(id) ON DELETE SET NULL,
    FOREIGN KEY (absence_id) REFERENCES absence(id) ON DELETE SET NULL,
    FOREIGN KEY (approver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
