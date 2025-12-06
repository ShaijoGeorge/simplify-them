<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251206185520 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455CDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455D0A3F5C2 FOREIGN KEY (head_of_family_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649CDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455CDEADB2A');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455D0A3F5C2');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649CDEADB2A');
    }
}
