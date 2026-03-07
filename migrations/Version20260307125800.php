<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307125800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create bonus_rate table for actuarial master data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE bonus_rate (
            id INT AUTO_INCREMENT NOT NULL,
            lic_plan_id INT NOT NULL,
            financial_year VARCHAR(10) NOT NULL,
            simple_reversionary_bonus NUMERIC(8, 2) NOT NULL,
            final_additional_bonus NUMERIC(8, 2) DEFAULT NULL,
            loyalty_addition NUMERIC(8, 2) DEFAULT NULL,
            created_at DATETIME DEFAULT NULL,
            created_by VARCHAR(255) DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            updated_by VARCHAR(255) DEFAULT NULL,
            INDEX IDX_bonus_rate_lic_plan (lic_plan_id),
            UNIQUE INDEX uq_bonus_rate_plan_year (lic_plan_id, financial_year),
            PRIMARY KEY(id),
            CONSTRAINT FK_bonus_rate_lic_plan FOREIGN KEY (lic_plan_id) REFERENCES lic_plan (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE bonus_rate');
    }
}
