<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add AI auto_response fields to feedback';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE feedback ADD auto_response LONGTEXT DEFAULT NULL, ADD auto_response_generated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE feedback DROP auto_response, DROP auto_response_generated_at');
    }
}

