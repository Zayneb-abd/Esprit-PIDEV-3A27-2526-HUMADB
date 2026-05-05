<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260505021001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reaction DROP FOREIGN KEY FK_9D9F0A6A6BBD14E6');
        $this->addSql('ALTER TABLE reaction DROP FOREIGN KEY FK_9D9F0A6AFB3E248B');
        $this->addSql('DROP TABLE reaction');
        $this->addSql('ALTER TABLE participation CHANGE date_inscription date_inscription DATE NOT NULL, CHANGE statut statut VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reaction (id INT AUTO_INCREMENT NOT NULL, publication_id INT DEFAULT NULL, commentaire_id INT DEFAULT NULL, user_id INT NOT NULL, type VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, INDEX IDX_9D9F0A6A6BBD14E6 (publication_id), INDEX IDX_9D9F0A6AFB3E248B (commentaire_id), INDEX IDX_9D9F0A6AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE reaction ADD CONSTRAINT FK_9D9F0A6A6BBD14E6 FOREIGN KEY (publication_id) REFERENCES publication (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reaction ADD CONSTRAINT FK_9D9F0A6AFB3E248B FOREIGN KEY (commentaire_id) REFERENCES commentaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participation CHANGE date_inscription date_inscription DATE DEFAULT NULL, CHANGE statut statut VARCHAR(20) DEFAULT NULL');
    }
}
