<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260320051457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE policy_status_log (id INT AUTO_INCREMENT NOT NULL, old_status VARCHAR(20) NOT NULL, new_status VARCHAR(20) NOT NULL, triggered_by VARCHAR(50) NOT NULL, reason LONGTEXT DEFAULT NULL, paid_up_sum_assured NUMERIC(12, 2) DEFAULT NULL, transitioned_at DATETIME NOT NULL, policy_id INT NOT NULL, INDEX idx_status_log_policy (policy_id), INDEX idx_status_log_date (transitioned_at), INDEX idx_status_log_new_status (new_status), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE policy_status_log ADD CONSTRAINT FK_6CF63D1B2D29E3C6 FOREIGN KEY (policy_id) REFERENCES policy (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE policy_status_log DROP FOREIGN KEY FK_6CF63D1B2D29E3C6');
        $this->addSql('DROP TABLE policy_status_log');
    }
}
