-- Create the action_logs table
CREATE TABLE action_logs (
    id INT AUTO_INCREMENT NOT NULL,
    action VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    user_id INT DEFAULT NULL,
    INDEX IDX_866E7D52A76ED395 (user_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
