<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260419140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create ai_assistant table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('ai_assistant');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_prompt', 'text', ['notnull' => false]);
        $table->addColumn('ai_response', 'text', ['notnull' => false]);
        $table->addColumn('generated_content', 'text', ['notnull' => true]);
        $table->addColumn('action', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['notnull' => false]);
        $table->addColumn('updated_at', 'datetime', ['notnull' => true]);
        $table->addColumn('publication_id', 'integer', ['notnull' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        
        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint(
            'fk_ai_assistant_publication',
            'publication_id',
            'publication',
            ['onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            'fk_ai_assistant_user',
            'user_id',
            'users',
            ['onDelete' => 'CASCADE']
        );
        
        $table->addIndex(['user_id'], 'idx_ai_assistant_user');
        $table->addIndex(['publication_id'], 'idx_ai_assistant_publication');
        $table->addIndex(['created_at'], 'idx_ai_assistant_created_at');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('ai_assistant');
    }
}
