<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add sent timestamp for feedback auto response';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE feedback ADD auto_response_sent_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE feedback DROP auto_response_sent_at');
    }
}

