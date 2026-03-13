<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260313191624 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE claim (id INT AUTO_INCREMENT NOT NULL, claim_type VARCHAR(30) NOT NULL, claim_date DATE NOT NULL, claimed_amount NUMERIC(10, 2) NOT NULL, status VARCHAR(30) NOT NULL, settled_amount NUMERIC(10, 2) DEFAULT NULL, settlement_date DATE DEFAULT NULL, claimant_name VARCHAR(200) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL, created_by VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, updated_by VARCHAR(255) DEFAULT NULL, policy_id INT NOT NULL, agency_id INT NOT NULL, INDEX IDX_A769DE272D29E3C6 (policy_id), INDEX IDX_A769DE27CDEADB2A (agency_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE claim ADD CONSTRAINT FK_A769DE272D29E3C6 FOREIGN KEY (policy_id) REFERENCES policy (id)');
        $this->addSql('ALTER TABLE claim ADD CONSTRAINT FK_A769DE27CDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE claim DROP FOREIGN KEY FK_A769DE272D29E3C6');
        $this->addSql('ALTER TABLE claim DROP FOREIGN KEY FK_A769DE27CDEADB2A');
        $this->addSql('DROP TABLE claim');
    }
}
