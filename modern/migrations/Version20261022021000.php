<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261022021000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert oversized legacy VARCHAR fields to utf8mb4-safe TEXT columns.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This migration can only run on MySQL or MariaDB.',
        );

        $this->addSql(
            'ALTER TABLE aspect CHANGE comment comment TEXT DEFAULT NULL, CHANGE description description TEXT DEFAULT NULL',
        );
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'VARCHAR(10000) is not safe on an utf8mb4 table because it can exceed the InnoDB row-size limit.',
        );
    }
}
