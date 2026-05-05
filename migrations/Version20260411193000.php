<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260411193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reputation_score column to users table';
    }

    public function up(Schema $schema): void
    {
        // Add reputation_score column to users table
        $this->addSql('ALTER TABLE users ADD COLUMN IF NOT EXISTS reputation_score INT DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        // Remove reputation_score column
        $this->addSql('ALTER TABLE users DROP COLUMN IF EXISTS reputation_score');
    }
}
