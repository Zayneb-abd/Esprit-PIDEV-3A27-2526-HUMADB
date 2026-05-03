<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add statut column to participation table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE participation ADD COLUMN IF NOT EXISTS statut VARCHAR(20) DEFAULT 'en attente'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participation DROP COLUMN IF EXISTS statut');
    }
}
