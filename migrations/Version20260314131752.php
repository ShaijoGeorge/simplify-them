<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260314131752 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE policy_document (id INT AUTO_INCREMENT NOT NULL, document_type VARCHAR(50) NOT NULL, file_name VARCHAR(255) NOT NULL, file_path VARCHAR(500) NOT NULL, uploaded_at DATETIME DEFAULT NULL, uploaded_by VARCHAR(255) DEFAULT NULL, policy_id INT NOT NULL, INDEX IDX_F9BE527B2D29E3C6 (policy_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE policy_document ADD CONSTRAINT FK_F9BE527B2D29E3C6 FOREIGN KEY (policy_id) REFERENCES policy (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE policy_document DROP FOREIGN KEY FK_F9BE527B2D29E3C6');
        $this->addSql('DROP TABLE policy_document');
    }
}
