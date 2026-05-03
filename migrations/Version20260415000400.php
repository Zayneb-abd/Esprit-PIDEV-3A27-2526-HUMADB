<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415000400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add priority column to feedback';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE feedback ADD priority VARCHAR(20) DEFAULT 'normal' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE feedback DROP priority');
    }
}
