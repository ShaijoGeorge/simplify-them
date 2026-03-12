<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260312143501 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add client_transaction table; refactor premium_receipt columns';
    }

    public function up(Schema $schema): void
    {
        // Disable strict mode so zero-dates in payment_date don't block the ALTER TABLE
        $this->addSql("SET SESSION sql_mode = ''");

        // Backfill any zero-dates to NULL before enforcing NOT NULL on payment_date
        $this->addSql("UPDATE premium_receipt SET payment_date = NULL WHERE CAST(payment_date AS CHAR) = '0000-00-00'");

        $this->addSql('CREATE TABLE client_transaction (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, amount NUMERIC(10, 2) NOT NULL, description LONGTEXT DEFAULT NULL, transaction_date DATE NOT NULL, created_at DATETIME DEFAULT NULL, created_by VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, updated_by VARCHAR(255) DEFAULT NULL, client_id INT NOT NULL, agency_id INT NOT NULL, premium_receipt_id INT DEFAULT NULL, INDEX IDX_737C20EA19EB6921 (client_id), INDEX IDX_737C20EACDEADB2A (agency_id), INDEX IDX_737C20EAF3EE59D5 (premium_receipt_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE client_transaction ADD CONSTRAINT FK_737C20EA19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE client_transaction ADD CONSTRAINT FK_737C20EACDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
        $this->addSql('ALTER TABLE client_transaction ADD CONSTRAINT FK_737C20EAF3EE59D5 FOREIGN KEY (premium_receipt_id) REFERENCES premium_receipt (id)');
        $this->addSql('ALTER TABLE premium_receipt ADD collected_from_client NUMERIC(10, 2) DEFAULT NULL, ADD collection_date DATE DEFAULT NULL, DROP lic_fine_amount, DROP collected_date, DROP collection_method, DROP paid_to_lic_date, DROP collected_amount, DROP paid_to_lic_amount, DROP payment_channel, CHANGE payment_date payment_date DATE NOT NULL, CHANGE base_premium amount NUMERIC(10, 2) NOT NULL, CHANGE status payment_mode VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client_transaction DROP FOREIGN KEY FK_737C20EA19EB6921');
        $this->addSql('ALTER TABLE client_transaction DROP FOREIGN KEY FK_737C20EACDEADB2A');
        $this->addSql('ALTER TABLE client_transaction DROP FOREIGN KEY FK_737C20EAF3EE59D5');
        $this->addSql('DROP TABLE client_transaction');
        $this->addSql('ALTER TABLE premium_receipt ADD collection_method VARCHAR(20) DEFAULT NULL, ADD paid_to_lic_date DATE DEFAULT NULL, ADD collected_amount NUMERIC(10, 2) DEFAULT NULL, ADD paid_to_lic_amount NUMERIC(10, 2) DEFAULT NULL, ADD payment_channel VARCHAR(20) DEFAULT NULL, CHANGE payment_date payment_date DATE DEFAULT NULL, CHANGE amount base_premium NUMERIC(10, 2) NOT NULL, CHANGE payment_mode status VARCHAR(20) NOT NULL, CHANGE collected_from_client lic_fine_amount NUMERIC(10, 2) DEFAULT NULL, CHANGE collection_date collected_date DATE DEFAULT NULL');
    }
}