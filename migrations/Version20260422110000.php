<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at to reaction_publication';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reaction_publication ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reaction_publication DROP created_at');
    }
}
