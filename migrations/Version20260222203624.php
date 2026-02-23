<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222203624 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE policy ADD entry_age INT DEFAULT NULL, ADD life_assured_name VARCHAR(255) DEFAULT NULL, ADD life_assured_dob DATE DEFAULT NULL, ADD life_assured_gender VARCHAR(20) DEFAULT NULL, ADD lic_bond_number VARCHAR(50) DEFAULT NULL, ADD lic_branch VARCHAR(100) DEFAULT NULL, ADD notes LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE policy DROP entry_age, DROP life_assured_name, DROP life_assured_dob, DROP life_assured_gender, DROP lic_bond_number, DROP lic_branch, DROP notes');
    }
}
