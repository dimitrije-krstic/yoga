<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210322213321 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_category ON event (category)');
        $this->addSql('CREATE INDEX idx_start ON event (start)');
        $this->addSql('CREATE INDEX idx_published ON event (published)');
        $this->addSql('CREATE INDEX idx_cancelled ON event (cancelled)');
        $this->addSql('CREATE INDEX idx_is_read ON message (is_read)');
        $this->addSql('CREATE INDEX idx_subject ON message_thread (subject)');
        $this->addSql('CREATE INDEX idx_spam ON message_thread (spam)');
        $this->addSql('CREATE INDEX idx_admin ON message_thread (admin)');
        $this->addSql('CREATE INDEX idx_type ON message_thread (type)');
        $this->addSql('CREATE INDEX idx_title ON post (title)');
        $this->addSql('CREATE INDEX idx_published_at ON post (published_at)');
        $this->addSql('CREATE INDEX idx_category ON post (category)');
        $this->addSql('CREATE INDEX idx_slug ON user (slug)');
        $this->addSql('CREATE INDEX idx_name ON user (name)');
        $this->addSql('CREATE INDEX idx_accepted_terms_of_use ON user (accepted_terms_of_use)');
        $this->addSql('CREATE INDEX idx_deleted_at ON user (deleted_at)');
        $this->addSql('CREATE INDEX idx_verified ON user (verified)');
        $this->addSql('CREATE INDEX idx_account_publicly_visible ON user (account_publicly_visible)');
        $this->addSql('CREATE INDEX idx_current_location ON user (current_location)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_category ON event');
        $this->addSql('DROP INDEX idx_start ON event');
        $this->addSql('DROP INDEX idx_published ON event');
        $this->addSql('DROP INDEX idx_cancelled ON event');
        $this->addSql('DROP INDEX idx_is_read ON message');
        $this->addSql('DROP INDEX idx_subject ON message_thread');
        $this->addSql('DROP INDEX idx_spam ON message_thread');
        $this->addSql('DROP INDEX idx_admin ON message_thread');
        $this->addSql('DROP INDEX idx_type ON message_thread');
        $this->addSql('DROP INDEX idx_title ON post');
        $this->addSql('DROP INDEX idx_published_at ON post');
        $this->addSql('DROP INDEX idx_category ON post');
        $this->addSql('DROP INDEX idx_slug ON user');
        $this->addSql('DROP INDEX idx_name ON user');
        $this->addSql('DROP INDEX idx_accepted_terms_of_use ON user');
        $this->addSql('DROP INDEX idx_deleted_at ON user');
        $this->addSql('DROP INDEX idx_verified ON user');
        $this->addSql('DROP INDEX idx_account_publicly_visible ON user');
        $this->addSql('DROP INDEX idx_current_location ON user');
    }
}
