<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305170509 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE survival_benefit (id INT AUTO_INCREMENT NOT NULL, due_date DATE NOT NULL, amount NUMERIC(10, 2) NOT NULL, percentage_of_sa NUMERIC(5, 2) NOT NULL, status VARCHAR(20) NOT NULL, collected_date DATE DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL, created_by VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, updated_by VARCHAR(255) DEFAULT NULL, policy_id INT NOT NULL, INDEX IDX_542111F42D29E3C6 (policy_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE survival_benefit ADD CONSTRAINT FK_542111F42D29E3C6 FOREIGN KEY (policy_id) REFERENCES policy (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE survival_benefit DROP FOREIGN KEY FK_542111F42D29E3C6');
        $this->addSql('DROP TABLE survival_benefit');
    }
}
