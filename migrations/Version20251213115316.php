<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251213115316 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commission_rule (id INT AUTO_INCREMENT NOT NULL, policy_year_from INT NOT NULL, policy_year_to INT NOT NULL, commission_rate NUMERIC(5, 2) NOT NULL, lic_plan_id INT NOT NULL, INDEX IDX_3496B2E80A61B47 (lic_plan_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE commission_rule ADD CONSTRAINT FK_3496B2E80A61B47 FOREIGN KEY (lic_plan_id) REFERENCES lic_plan (id)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455CDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455D0A3F5C2 FOREIGN KEY (head_of_family_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D051619EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D051680A61B47 FOREIGN KEY (lic_plan_id) REFERENCES lic_plan (id)');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D0516CDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
        $this->addSql('ALTER TABLE premium_receipt ADD commission_earned NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE premium_receipt ADD CONSTRAINT FK_AB89A2CB2D29E3C6 FOREIGN KEY (policy_id) REFERENCES policy (id)');
        $this->addSql('ALTER TABLE premium_receipt ADD CONSTRAINT FK_AB89A2CBCDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649CDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commission_rule DROP FOREIGN KEY FK_3496B2E80A61B47');
        $this->addSql('DROP TABLE commission_rule');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455CDEADB2A');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455D0A3F5C2');
        $this->addSql('ALTER TABLE policy DROP FOREIGN KEY FK_F07D051619EB6921');
        $this->addSql('ALTER TABLE policy DROP FOREIGN KEY FK_F07D051680A61B47');
        $this->addSql('ALTER TABLE policy DROP FOREIGN KEY FK_F07D0516CDEADB2A');
        $this->addSql('ALTER TABLE premium_receipt DROP FOREIGN KEY FK_AB89A2CB2D29E3C6');
        $this->addSql('ALTER TABLE premium_receipt DROP FOREIGN KEY FK_AB89A2CBCDEADB2A');
        $this->addSql('ALTER TABLE premium_receipt DROP commission_earned');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649CDEADB2A');
    }
}
