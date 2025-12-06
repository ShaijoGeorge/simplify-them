<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251206195256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE policy (id INT AUTO_INCREMENT NOT NULL, policy_number VARCHAR(50) NOT NULL, commencement_date DATE NOT NULL, sum_assured NUMERIC(10, 2) NOT NULL, policy_term INT NOT NULL, premium_paying_term INT NOT NULL, premium_mode VARCHAR(20) NOT NULL, basic_premium NUMERIC(10, 2) NOT NULL, gst NUMERIC(10, 2) NOT NULL, total_premium NUMERIC(10, 2) NOT NULL, next_due_date DATE DEFAULT NULL, maturity_date DATE DEFAULT NULL, status VARCHAR(20) NOT NULL, client_id INT NOT NULL, lic_plan_id INT NOT NULL, agency_id INT NOT NULL, INDEX IDX_F07D051619EB6921 (client_id), INDEX IDX_F07D051680A61B47 (lic_plan_id), INDEX IDX_F07D0516CDEADB2A (agency_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D051619EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D051680A61B47 FOREIGN KEY (lic_plan_id) REFERENCES lic_plan (id)');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D0516CDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455CDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455D0A3F5C2 FOREIGN KEY (head_of_family_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649CDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE policy DROP FOREIGN KEY FK_F07D051619EB6921');
        $this->addSql('ALTER TABLE policy DROP FOREIGN KEY FK_F07D051680A61B47');
        $this->addSql('ALTER TABLE policy DROP FOREIGN KEY FK_F07D0516CDEADB2A');
        $this->addSql('DROP TABLE policy');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455CDEADB2A');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455D0A3F5C2');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649CDEADB2A');
    }
}
