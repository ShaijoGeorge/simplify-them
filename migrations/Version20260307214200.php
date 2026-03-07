<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260307214200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE premium_table (id INT AUTO_INCREMENT NOT NULL, entry_age INT NOT NULL, policy_term INT NOT NULL, annual_premium_per_thousand NUMERIC(8, 2) NOT NULL, created_at DATETIME DEFAULT NULL, created_by VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, updated_by VARCHAR(255) DEFAULT NULL, lic_plan_id INT NOT NULL, INDEX IDX_4BA9D78080A61B47 (lic_plan_id), UNIQUE INDEX uq_premium_table_plan_age_term (lic_plan_id, entry_age, policy_term), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE premium_table ADD CONSTRAINT FK_4BA9D78080A61B47 FOREIGN KEY (lic_plan_id) REFERENCES lic_plan (id)');
        $this->addSql('ALTER TABLE bonus_rate RENAME INDEX idx_bonus_rate_lic_plan TO IDX_72E781B80A61B47');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE premium_table DROP FOREIGN KEY FK_4BA9D78080A61B47');
        $this->addSql('DROP TABLE premium_table');
        $this->addSql('ALTER TABLE bonus_rate RENAME INDEX idx_72e781b80a61b47 TO IDX_bonus_rate_lic_plan');
    }
}
