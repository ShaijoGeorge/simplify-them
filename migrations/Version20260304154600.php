<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add annualPremium and modalRebate columns to the policy table.
 */
final class Version20260304154600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add annual_premium and modal_rebate columns to policy table for modal rebate feature';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE policy ADD annual_premium NUMERIC(12, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE policy ADD modal_rebate NUMERIC(6, 4) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE policy DROP annual_premium');
        $this->addSql('ALTER TABLE policy DROP modal_rebate');
    }
}
