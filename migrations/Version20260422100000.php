<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create jours_feries table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
CREATE TABLE jours_feries (
    id INT AUTO_INCREMENT NOT NULL,
    nom VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    pays VARCHAR(10) NOT NULL,
    type VARCHAR(50) NOT NULL,
    annee INT NOT NULL,
    date_creation DATETIME NOT NULL,
    UNIQUE INDEX uniq_jours_feries_date_pays (date, pays),
    INDEX idx_jours_feries_annee_pays (annee, pays),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE jours_feries');
    }
}
