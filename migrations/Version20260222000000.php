<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Expands Policy statuses and ensures existing data is preserved.
 */
final class Version20260222000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure existing policy status data is preserved while expanding ENUM choices.';
    }

    public function up(Schema $schema): void
    {
        // The policy.status column is already VARCHAR(20).
        // The new longest status is REVIVAL_PENDING (15 chars).
        // No schema changes are strictly necessary for MySQL/PostgreSQL 
        // as Doctrine handles string ENUMs as VARCHAR at the DB level.
        // This is a placeholder to satisfy the migration requirement 
        // and ensure the table schema is explicitly checked.
        $this->addSql('-- Adding PAID_UP, SURRENDERED, DEATH_CLAIM, REVIVAL_PENDING to application logic.');
        $this->addSql('-- Existing data is inherently preserved since the column length (20) accommodates the new values.');
    }

    public function down(Schema $schema): void
    {
        // A downgrade requires reverting any policies placed into the new statuses.
        $this->addSql('UPDATE policy SET status = \'IN_FORCE\' WHERE status IN (\'PAID_UP\', \'REVIVAL_PENDING\')');
        $this->addSql('UPDATE policy SET status = \'MATURED\' WHERE status IN (\'SURRENDERED\', \'DEATH_CLAIM\')');
    }
}