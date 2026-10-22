<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates the current schema for a fresh installation. Existing restored
 * legacy databases are intentionally left untouched by this baseline.
 */
final class Version20261022010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the baseline JSN schema for fresh installations';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->createSchemaManager()->tablesExist(['person'])) {
            return;
        }

        $this->addSql('CREATE TABLE alternate_name (id INT AUTO_INCREMENT NOT NULL, display_name VARCHAR(255) NOT NULL, person_id INT DEFAULT NULL, INDEX IDX_AD89C8AB217BBB47 (person_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aspect (id INT NOT NULL, type VARCHAR(255) NOT NULL, date_exact INT DEFAULT NULL, date_from INT DEFAULT NULL, date_to INT DEFAULT NULL, occupation VARCHAR(255) DEFAULT NULL, affiliation VARCHAR(255) DEFAULT NULL, occupation_slug VARCHAR(255) DEFAULT NULL, comment TEXT DEFAULT NULL, description TEXT DEFAULT NULL, raw_xml MEDIUMTEXT DEFAULT NULL, person_id INT DEFAULT NULL, source_id INT DEFAULT NULL, INDEX IDX_F083C355217BBB47 (person_id), INDEX IDX_F083C355953C1C61 (source_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aspect_place (aspect_id INT NOT NULL, place_id INT NOT NULL, INDEX IDX_46FBDE1B98507F8C (aspect_id), INDEX IDX_46FBDE1BDA6A219 (place_id), PRIMARY KEY (aspect_id, place_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aspect_subject (aspect_id INT NOT NULL, subject_id INT NOT NULL, INDEX IDX_BFA5919898507F8C (aspect_id), INDEX IDX_BFA5919823EDC87 (subject_id), PRIMARY KEY (aspect_id, subject_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE person (id INT NOT NULL, display_name VARCHAR(255) DEFAULT NULL, list_name VARCHAR(255) DEFAULT NULL, short_name VARCHAR(255) DEFAULT NULL, group_letter VARCHAR(1) DEFAULT NULL, name_for_sorting VARCHAR(255) DEFAULT NULL, is_jesuit TINYINT NOT NULL, viaf_id INT DEFAULT NULL, lastmod DATETIME DEFAULT NULL, date_of_birth INT DEFAULT NULL, date_of_death INT DEFAULT NULL, is_math_nat TINYINT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE person_subject (person_id INT NOT NULL, subject_id INT NOT NULL, INDEX IDX_D9508DF3217BBB47 (person_id), INDEX IDX_D9508DF323EDC87 (subject_id), PRIMARY KEY (person_id, subject_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE person_place (person_id INT NOT NULL, place_id INT NOT NULL, INDEX IDX_D82B4C09217BBB47 (person_id), INDEX IDX_D82B4C09DA6A219 (place_id), PRIMARY KEY (person_id, place_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE person_source (person_id INT NOT NULL, source_id INT NOT NULL, INDEX IDX_C32F4FC5217BBB47 (person_id), INDEX IDX_C32F4FC5953C1C61 (source_id), PRIMARY KEY (person_id, source_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE place (id INT AUTO_INCREMENT NOT NULL, place_name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, country VARCHAR(255) DEFAULT NULL, continent VARCHAR(255) DEFAULT NULL, latitude NUMERIC(11, 8) DEFAULT NULL, longitude NUMERIC(11, 8) DEFAULT NULL, INDEX place_name (place_name), INDEX slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE relations (id INT AUTO_INCREMENT NOT NULL, class VARCHAR(255) DEFAULT NULL, context VARCHAR(255) DEFAULT NULL, value VARCHAR(255) DEFAULT NULL, source_id INT DEFAULT NULL, target_id INT DEFAULT NULL, aspect_id INT DEFAULT NULL, UNIQUE INDEX unique_relation (source_id, target_id, class, context, value), INDEX IDX_146CBF78953C1C61 (source_id), INDEX IDX_146CBF78158E0B66 (target_id), INDEX IDX_146CBF7898507F8C (aspect_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE source (id INT NOT NULL, genre VARCHAR(255) NOT NULL, title VARCHAR(1000) DEFAULT NULL, series_title VARCHAR(1000) DEFAULT NULL, authors JSON DEFAULT NULL, editors JSON DEFAULT NULL, publisher VARCHAR(255) DEFAULT NULL, place VARCHAR(255) DEFAULT NULL, date_issued INT DEFAULT NULL, date_captured DATETIME DEFAULT NULL, url VARCHAR(1000) DEFAULT NULL, note VARCHAR(255) DEFAULT NULL, payload MEDIUMTEXT NOT NULL, source_group_id INT DEFAULT NULL, INDEX IDX_5F8A7F73BD74EF95 (source_group_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE source_group (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, full_cite VARCHAR(255) NOT NULL, color VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, INDEX slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE subject (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, is_mathnat TINYINT NOT NULL, INDEX slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE subject_group (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, INDEX slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE subject_group_subject (subject_group_id INT NOT NULL, subject_id INT NOT NULL, INDEX IDX_42E493FC629992F (subject_group_id), INDEX IDX_42E493F23EDC87 (subject_id), PRIMARY KEY (subject_group_id, subject_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE alternate_name ADD CONSTRAINT FK_AD89C8AB217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE aspect ADD CONSTRAINT FK_F083C355217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE aspect ADD CONSTRAINT FK_F083C355953C1C61 FOREIGN KEY (source_id) REFERENCES source (id)');
        $this->addSql('ALTER TABLE aspect_place ADD CONSTRAINT FK_46FBDE1B98507F8C FOREIGN KEY (aspect_id) REFERENCES aspect (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aspect_place ADD CONSTRAINT FK_46FBDE1BDA6A219 FOREIGN KEY (place_id) REFERENCES place (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aspect_subject ADD CONSTRAINT FK_BFA5919898507F8C FOREIGN KEY (aspect_id) REFERENCES aspect (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aspect_subject ADD CONSTRAINT FK_BFA5919823EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE person_subject ADD CONSTRAINT FK_D9508DF3217BBB47 FOREIGN KEY (person_id) REFERENCES person (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE person_subject ADD CONSTRAINT FK_D9508DF323EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE person_place ADD CONSTRAINT FK_D82B4C09217BBB47 FOREIGN KEY (person_id) REFERENCES person (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE person_place ADD CONSTRAINT FK_D82B4C09DA6A219 FOREIGN KEY (place_id) REFERENCES place (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE person_source ADD CONSTRAINT FK_C32F4FC5217BBB47 FOREIGN KEY (person_id) REFERENCES person (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE person_source ADD CONSTRAINT FK_C32F4FC5953C1C61 FOREIGN KEY (source_id) REFERENCES source (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE relations ADD CONSTRAINT FK_146CBF78953C1C61 FOREIGN KEY (source_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE relations ADD CONSTRAINT FK_146CBF78158E0B66 FOREIGN KEY (target_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE relations ADD CONSTRAINT FK_146CBF7898507F8C FOREIGN KEY (aspect_id) REFERENCES aspect (id)');
        $this->addSql('ALTER TABLE source ADD CONSTRAINT FK_5F8A7F73BD74EF95 FOREIGN KEY (source_group_id) REFERENCES source_group (id)');
        $this->addSql('ALTER TABLE subject_group_subject ADD CONSTRAINT FK_42E493FC629992F FOREIGN KEY (subject_group_id) REFERENCES subject_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subject_group_subject ADD CONSTRAINT FK_42E493F23EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'The baseline may represent a restored legacy database and must never drop its tables automatically.',
        );

        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alternate_name DROP FOREIGN KEY FK_AD89C8AB217BBB47');
        $this->addSql('ALTER TABLE aspect DROP FOREIGN KEY FK_F083C355217BBB47');
        $this->addSql('ALTER TABLE aspect DROP FOREIGN KEY FK_F083C355953C1C61');
        $this->addSql('ALTER TABLE aspect_place DROP FOREIGN KEY FK_46FBDE1B98507F8C');
        $this->addSql('ALTER TABLE aspect_place DROP FOREIGN KEY FK_46FBDE1BDA6A219');
        $this->addSql('ALTER TABLE aspect_subject DROP FOREIGN KEY FK_BFA5919898507F8C');
        $this->addSql('ALTER TABLE aspect_subject DROP FOREIGN KEY FK_BFA5919823EDC87');
        $this->addSql('ALTER TABLE person_subject DROP FOREIGN KEY FK_D9508DF3217BBB47');
        $this->addSql('ALTER TABLE person_subject DROP FOREIGN KEY FK_D9508DF323EDC87');
        $this->addSql('ALTER TABLE person_place DROP FOREIGN KEY FK_D82B4C09217BBB47');
        $this->addSql('ALTER TABLE person_place DROP FOREIGN KEY FK_D82B4C09DA6A219');
        $this->addSql('ALTER TABLE person_source DROP FOREIGN KEY FK_C32F4FC5217BBB47');
        $this->addSql('ALTER TABLE person_source DROP FOREIGN KEY FK_C32F4FC5953C1C61');
        $this->addSql('ALTER TABLE relations DROP FOREIGN KEY FK_146CBF78953C1C61');
        $this->addSql('ALTER TABLE relations DROP FOREIGN KEY FK_146CBF78158E0B66');
        $this->addSql('ALTER TABLE relations DROP FOREIGN KEY FK_146CBF7898507F8C');
        $this->addSql('ALTER TABLE source DROP FOREIGN KEY FK_5F8A7F73BD74EF95');
        $this->addSql('ALTER TABLE subject_group_subject DROP FOREIGN KEY FK_42E493FC629992F');
        $this->addSql('ALTER TABLE subject_group_subject DROP FOREIGN KEY FK_42E493F23EDC87');
        $this->addSql('DROP TABLE alternate_name');
        $this->addSql('DROP TABLE aspect');
        $this->addSql('DROP TABLE aspect_place');
        $this->addSql('DROP TABLE aspect_subject');
        $this->addSql('DROP TABLE person');
        $this->addSql('DROP TABLE person_subject');
        $this->addSql('DROP TABLE person_place');
        $this->addSql('DROP TABLE person_source');
        $this->addSql('DROP TABLE place');
        $this->addSql('DROP TABLE relations');
        $this->addSql('DROP TABLE source');
        $this->addSql('DROP TABLE source_group');
        $this->addSql('DROP TABLE subject');
        $this->addSql('DROP TABLE subject_group');
        $this->addSql('DROP TABLE subject_group_subject');
    }
}
