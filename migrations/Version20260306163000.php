<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260306163000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add PAN to agency, rename commission_earned to gross_commission, add TDS columns to premium_receipt';
    }

    public function up(Schema $schema): void
    {
        // Agency: add PAN number
        $this->addSql('ALTER TABLE agency ADD pan_number VARCHAR(10) DEFAULT NULL');

        // PremiumReceipt: rename commission_earned → gross_commission
        $this->addSql('ALTER TABLE premium_receipt RENAME COLUMN commission_earned TO gross_commission');

        // PremiumReceipt: add TDS columns
        $this->addSql('ALTER TABLE premium_receipt ADD tds_on_commission NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE premium_receipt ADD net_commission NUMERIC(10, 2) DEFAULT NULL');

        // Backfill historical data: net = gross, tds = 0
        $this->addSql('UPDATE premium_receipt SET net_commission = gross_commission, tds_on_commission = 0 WHERE gross_commission IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE premium_receipt DROP COLUMN net_commission');
        $this->addSql('ALTER TABLE premium_receipt DROP COLUMN tds_on_commission');
        $this->addSql('ALTER TABLE premium_receipt RENAME COLUMN gross_commission TO commission_earned');
        $this->addSql('ALTER TABLE agency DROP COLUMN pan_number');
    }
}
