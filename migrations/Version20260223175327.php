<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223175327 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE nominee (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(200) NOT NULL, relationship VARCHAR(50) NOT NULL, dob DATE DEFAULT NULL, guardian_name VARCHAR(200) DEFAULT NULL, mobile VARCHAR(20) DEFAULT NULL, share_percentage NUMERIC(5, 2) NOT NULL, aadhar VARCHAR(20) DEFAULT NULL, created_at DATETIME DEFAULT NULL, created_by VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, updated_by VARCHAR(255) DEFAULT NULL, policy_id INT NOT NULL, INDEX IDX_FFD0B2232D29E3C6 (policy_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE nominee ADD CONSTRAINT FK_FFD0B2232D29E3C6 FOREIGN KEY (policy_id) REFERENCES policy (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nominee DROP FOREIGN KEY FK_FFD0B2232D29E3C6');
        $this->addSql('DROP TABLE nominee');
    }
}
