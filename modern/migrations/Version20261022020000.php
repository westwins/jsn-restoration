<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261022020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace the obsolete Doctrine json_array column markers with validated native MariaDB JSON columns.';
    }

    public function isTransactional(): bool
    {
        // MariaDB commits ALTER TABLE statements implicitly.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This migration can only run on MySQL or MariaDB.',
        );

        $invalidAuthors = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM source WHERE authors IS NOT NULL AND JSON_VALID(authors) = 0',
        );
        $invalidEditors = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM source WHERE editors IS NOT NULL AND JSON_VALID(editors) = 0',
        );

        $this->abortIf(
            $invalidAuthors > 0 || $invalidEditors > 0,
            sprintf(
                'Refusing JSON conversion: %d invalid authors values and %d invalid editors values.',
                $invalidAuthors,
                $invalidEditors,
            ),
        );

        $this->addSql(
            'ALTER TABLE source CHANGE authors authors JSON DEFAULT NULL, CHANGE editors editors JSON DEFAULT NULL',
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This migration can only run on MySQL or MariaDB.',
        );

        $this->addSql(
            "ALTER TABLE source CHANGE authors authors LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json_array)', CHANGE editors editors LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json_array)'",
        );
    }
}
