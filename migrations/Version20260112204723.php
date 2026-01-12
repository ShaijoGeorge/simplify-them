<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260112204723 extends AbstractMigration
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
        $this->addSql('ALTER TABLE commission_rule ADD CONSTRAINT FK_3496B2E80A61B47 FOREIGN KEY (lic_plan_id) REFERENCES lic_plan (id)');
        $this->addSql('ALTER TABLE lic_plan ADD CONSTRAINT FK_3475C2247BF3C49B FOREIGN KEY (plan_type_id) REFERENCES lic_plan_type (id)');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D051619EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D051680A61B47 FOREIGN KEY (lic_plan_id) REFERENCES lic_plan (id)');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D0516CDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
        $this->addSql('ALTER TABLE premium_receipt ADD CONSTRAINT FK_AB89A2CB2D29E3C6 FOREIGN KEY (policy_id) REFERENCES policy (id)');
        $this->addSql('ALTER TABLE premium_receipt ADD CONSTRAINT FK_AB89A2CBCDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649CDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6498E0E3CA6 FOREIGN KEY (user_role_id) REFERENCES role (id)');
    }

    public function down(Schema $schema): void
    {
        $this->dropFkIfExists('client', 'FK_C7440455CDEADB2A');
        $this->dropFkIfExists('client', 'FK_C7440455D0A3F5C2');
        $this->dropFkIfExists('commission_rule', 'FK_3496B2E80A61B47');
        $this->dropFkIfExists('lic_plan', 'FK_3475C2247BF3C49B');
        $this->dropFkIfExists('policy', 'FK_F07D051619EB6921');
        $this->dropFkIfExists('policy', 'FK_F07D051680A61B47');
        $this->dropFkIfExists('policy', 'FK_F07D0516CDEADB2A');
        $this->dropFkIfExists('premium_receipt', 'FK_AB89A2CB2D29E3C6');
        $this->dropFkIfExists('premium_receipt', 'FK_AB89A2CBCDEADB2A');
        $this->dropFkIfExists('user', 'FK_8D93D649CDEADB2A');
        $this->dropFkIfExists('user', 'FK_8D93D6498E0E3CA6');
    }

    /**
    * MySQL-safe foreign key drop
    */
    private function dropFkIfExists(string $table, string $fkName): void
    {
        $this->addSql("
            SET @fk := (
                SELECT COUNT(*)
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND TABLE_NAME = '{$table}'
                AND CONSTRAINT_NAME = '{$fkName}'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            );

            SET @sql := IF(
                @fk > 0,
                'ALTER TABLE {$table} DROP FOREIGN KEY {$fkName}',
                'SELECT 1'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
    }
}
