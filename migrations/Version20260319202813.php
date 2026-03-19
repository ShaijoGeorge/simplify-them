<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260319202813 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE command_execution (id INT AUTO_INCREMENT NOT NULL, command_name VARCHAR(100) NOT NULL, status VARCHAR(20) NOT NULL, output LONGTEXT DEFAULT NULL, executed_at DATETIME NOT NULL, duration_ms INT DEFAULT NULL, exit_code INT DEFAULT NULL, INDEX idx_cmd_name (command_name), INDEX idx_cmd_executed_at (executed_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE command_execution');
    }
}
