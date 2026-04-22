<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for HumaBot chat messages table
 */
final class Version20260422020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create chat_messages table for HumaBot AI support chatbot';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE chat_messages (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, role VARCHAR(10) NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, session_id VARCHAR(255) NOT NULL, INDEX IDX_CHAT_MESSAGES_USER_ID (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE=InnoDB');
        $this->addSql('ALTER TABLE chat_messages ADD CONSTRAINT FK_CHAT_MESSAGES_USER_ID FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE chat_messages');
    }
}
