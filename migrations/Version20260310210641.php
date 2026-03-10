<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260310210641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client ADD wallet_balance NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE premium_receipt ADD lic_fine_amount NUMERIC(10, 2) DEFAULT NULL, ADD collected_amount NUMERIC(10, 2) DEFAULT NULL, ADD paid_to_lic_amount NUMERIC(10, 2) DEFAULT NULL, ADD payment_channel VARCHAR(20) DEFAULT NULL, DROP payment_date, DROP paid_to_lic_mode, DROP workflow_status, DROP amount_collected, DROP amount_paid_to_lic, CHANGE amount base_premium NUMERIC(10, 2) NOT NULL, CHANGE collection_date collected_date DATE DEFAULT NULL, CHANGE collection_mode collection_method VARCHAR(20) DEFAULT NULL, CHANGE payment_mode status VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client DROP wallet_balance');
        $this->addSql('ALTER TABLE premium_receipt ADD payment_date DATE NOT NULL, ADD collection_mode VARCHAR(20) DEFAULT NULL, ADD paid_to_lic_mode VARCHAR(30) DEFAULT NULL, ADD workflow_status VARCHAR(20) DEFAULT \'COMPLETED\' NOT NULL, ADD amount_collected NUMERIC(10, 2) DEFAULT NULL, ADD amount_paid_to_lic NUMERIC(10, 2) DEFAULT NULL, DROP lic_fine_amount, DROP collected_amount, DROP collection_method, DROP paid_to_lic_amount, DROP payment_channel, CHANGE base_premium amount NUMERIC(10, 2) NOT NULL, CHANGE status payment_mode VARCHAR(20) NOT NULL, CHANGE collected_date collection_date DATE DEFAULT NULL');
    }
}
