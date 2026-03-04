<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260304195751 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE policy_rider (id INT AUTO_INCREMENT NOT NULL, rider_type VARCHAR(30) NOT NULL, rider_sum_assured NUMERIC(10, 2) DEFAULT NULL, rider_premium NUMERIC(10, 2) NOT NULL, rider_start_date DATE NOT NULL, rider_end_date DATE DEFAULT NULL, is_active TINYINT NOT NULL, created_at DATETIME DEFAULT NULL, created_by VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, updated_by VARCHAR(255) DEFAULT NULL, policy_id INT NOT NULL, INDEX IDX_518799CE2D29E3C6 (policy_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE policy_rider ADD CONSTRAINT FK_518799CE2D29E3C6 FOREIGN KEY (policy_id) REFERENCES policy (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE policy_rider DROP FOREIGN KEY FK_518799CE2D29E3C6');
        $this->addSql('DROP TABLE policy_rider');
    }
}
