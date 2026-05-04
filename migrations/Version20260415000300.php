<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415000300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize users reset token columns for forgot password flow';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users MODIFY reset_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users MODIFY token_expiry DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Keep current safer schema on rollback as well.
    }
}

