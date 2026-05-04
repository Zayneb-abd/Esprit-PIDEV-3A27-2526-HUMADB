<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create approval_history table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
CREATE TABLE approval_history (
    id INT AUTO_INCREMENT NOT NULL,
    conge_id INT DEFAULT NULL,
    absence_id INT DEFAULT NULL,
    approver_id INT NOT NULL,
    action VARCHAR(20) NOT NULL,
    comment LONGTEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    INDEX IDX_5C31A3A9658A1D67 (conge_id),
    INDEX IDX_5C31A3A97346B2 (absence_id),
    INDEX IDX_5C31A3A9D86650F5 (approver_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql('ALTER TABLE approval_history ADD CONSTRAINT FK_5C31A3A9658A1D67 FOREIGN KEY (conge_id) REFERENCES conge (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE approval_history ADD CONSTRAINT FK_5C31A3A97346B2 FOREIGN KEY (absence_id) REFERENCES absence (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE approval_history ADD CONSTRAINT FK_5C31A3A9D86650F5 FOREIGN KEY (approver_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE approval_history');
    }
}
