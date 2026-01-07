<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260107191851 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE lic_plan_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455CDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455D0A3F5C2 FOREIGN KEY (head_of_family_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE commission_rule ADD CONSTRAINT FK_3496B2E80A61B47 FOREIGN KEY (lic_plan_id) REFERENCES lic_plan (id)');
        $this->addSql('ALTER TABLE lic_plan ADD plan_type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE lic_plan ADD CONSTRAINT FK_3475C2247BF3C49B FOREIGN KEY (plan_type_id) REFERENCES lic_plan_type (id)');
        $this->addSql('CREATE INDEX IDX_3475C2247BF3C49B ON lic_plan (plan_type_id)');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D051619EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D051680A61B47 FOREIGN KEY (lic_plan_id) REFERENCES lic_plan (id)');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D0516CDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
        $this->addSql('ALTER TABLE premium_receipt ADD CONSTRAINT FK_AB89A2CB2D29E3C6 FOREIGN KEY (policy_id) REFERENCES policy (id)');
        $this->addSql('ALTER TABLE premium_receipt ADD CONSTRAINT FK_AB89A2CBCDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649CDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
        $this->addSql('DROP INDEX IDX_75EA56E016BA31DB ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E0E3BD61CE ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0 ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE lic_plan_type');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455CDEADB2A');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455D0A3F5C2');
        $this->addSql('ALTER TABLE commission_rule DROP FOREIGN KEY FK_3496B2E80A61B47');
        $this->addSql('ALTER TABLE lic_plan DROP FOREIGN KEY FK_3475C2247BF3C49B');
        $this->addSql('DROP INDEX IDX_3475C2247BF3C49B ON lic_plan');
        $this->addSql('ALTER TABLE lic_plan DROP plan_type_id');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('ALTER TABLE policy DROP FOREIGN KEY FK_F07D051619EB6921');
        $this->addSql('ALTER TABLE policy DROP FOREIGN KEY FK_F07D051680A61B47');
        $this->addSql('ALTER TABLE policy DROP FOREIGN KEY FK_F07D0516CDEADB2A');
        $this->addSql('ALTER TABLE premium_receipt DROP FOREIGN KEY FK_AB89A2CB2D29E3C6');
        $this->addSql('ALTER TABLE premium_receipt DROP FOREIGN KEY FK_AB89A2CBCDEADB2A');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649CDEADB2A');
    }
}
