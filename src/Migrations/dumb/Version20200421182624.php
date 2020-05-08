<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200421182624 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX IDX_38FBB1679393F8FE ON hostel');
        $this->addSql('ALTER TABLE hostel ADD CONSTRAINT FK_38FBB167BF396750 FOREIGN KEY (id) REFERENCES user (partner_id)');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649FC68ACC0');
        $this->addSql('DROP INDEX IDX_8D93D649FC68ACC0 ON user');
        $this->addSql('ALTER TABLE user DROP hostel_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE hostel DROP FOREIGN KEY FK_38FBB167BF396750');
        $this->addSql('CREATE INDEX IDX_38FBB1679393F8FE ON hostel (user_id)');
        $this->addSql('ALTER TABLE user ADD hostel_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649FC68ACC0 FOREIGN KEY (hostel_id) REFERENCES hostel (id)');
        $this->addSql('CREATE INDEX IDX_8D93D649FC68ACC0 ON user (hostel_id)');
    }
}
