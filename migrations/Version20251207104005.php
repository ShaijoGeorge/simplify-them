<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251207104005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE premium_receipt (id INT AUTO_INCREMENT NOT NULL, receipt_number VARCHAR(50) NOT NULL, payment_date DATE NOT NULL, amount NUMERIC(10, 2) NOT NULL, payment_mode VARCHAR(20) NOT NULL, policy_id INT NOT NULL, agency_id INT NOT NULL, INDEX IDX_AB89A2CB2D29E3C6 (policy_id), INDEX IDX_AB89A2CBCDEADB2A (agency_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE premium_receipt ADD CONSTRAINT FK_AB89A2CB2D29E3C6 FOREIGN KEY (policy_id) REFERENCES policy (id)');
        $this->addSql('ALTER TABLE premium_receipt ADD CONSTRAINT FK_AB89A2CBCDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455CDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455D0A3F5C2 FOREIGN KEY (head_of_family_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D051619EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D051680A61B47 FOREIGN KEY (lic_plan_id) REFERENCES lic_plan (id)');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D0516CDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649CDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE premium_receipt DROP FOREIGN KEY FK_AB89A2CB2D29E3C6');
        $this->addSql('ALTER TABLE premium_receipt DROP FOREIGN KEY FK_AB89A2CBCDEADB2A');
        $this->addSql('DROP TABLE premium_receipt');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455CDEADB2A');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455D0A3F5C2');
        $this->addSql('ALTER TABLE policy DROP FOREIGN KEY FK_F07D051619EB6921');
        $this->addSql('ALTER TABLE policy DROP FOREIGN KEY FK_F07D051680A61B47');
        $this->addSql('ALTER TABLE policy DROP FOREIGN KEY FK_F07D0516CDEADB2A');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649CDEADB2A');
    }
}
