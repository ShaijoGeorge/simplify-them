<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260118153653 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client ADD created_at DATETIME DEFAULT NULL, ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE commission_rule ADD created_at DATETIME DEFAULT NULL, ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE lic_plan ADD created_at DATETIME DEFAULT NULL, ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE lic_plan_type ADD created_at DATETIME DEFAULT NULL, ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE module ADD created_at DATETIME DEFAULT NULL, ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE permission ADD created_at DATETIME DEFAULT NULL, ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE policy ADD created_at DATETIME DEFAULT NULL, ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE premium_receipt ADD created_at DATETIME DEFAULT NULL, ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE role ADD created_at DATETIME DEFAULT NULL, ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD created_at DATETIME DEFAULT NULL, ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client DROP created_at, DROP created_by, DROP updated_at, DROP updated_by');
        $this->addSql('ALTER TABLE commission_rule DROP created_at, DROP created_by, DROP updated_at, DROP updated_by');
        $this->addSql('ALTER TABLE lic_plan DROP created_at, DROP created_by, DROP updated_at, DROP updated_by');
        $this->addSql('ALTER TABLE lic_plan_type DROP created_at, DROP created_by, DROP updated_at, DROP updated_by');
        $this->addSql('ALTER TABLE module DROP created_at, DROP created_by, DROP updated_at, DROP updated_by');
        $this->addSql('ALTER TABLE permission DROP created_at, DROP created_by, DROP updated_at, DROP updated_by');
        $this->addSql('ALTER TABLE policy DROP created_at, DROP created_by, DROP updated_at, DROP updated_by');
        $this->addSql('ALTER TABLE premium_receipt DROP created_at, DROP created_by, DROP updated_at, DROP updated_by');
        $this->addSql('ALTER TABLE role DROP created_at, DROP created_by, DROP updated_at, DROP updated_by');
        $this->addSql('ALTER TABLE user DROP created_at, DROP created_by, DROP updated_at, DROP updated_by');
    }
}
