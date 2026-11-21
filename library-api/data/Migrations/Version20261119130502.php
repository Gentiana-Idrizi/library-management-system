<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261119130502 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial library schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE books (id SERIAL NOT NULL, title VARCHAR(255) NOT NULL, author VARCHAR(255) NOT NULL, isbn VARCHAR(20) NOT NULL, category VARCHAR(100) NOT NULL, stock INT DEFAULT 0 NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4A1B2A92CC1CF4E6 ON books (isbn)');
        $this->addSql('CREATE INDEX idx_books_category ON books (category)');
        $this->addSql('COMMENT ON COLUMN books.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN books.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN books.deleted_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE loans (id SERIAL NOT NULL, user_id INT NOT NULL, book_id INT NOT NULL, borrowed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, due_at DATE NOT NULL, returned_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, status VARCHAR(20) NOT NULL, borrow_fee NUMERIC(10, 2) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_82C24DBCA76ED395 ON loans (user_id)');
        $this->addSql('CREATE INDEX IDX_82C24DBC16A2B381 ON loans (book_id)');
        $this->addSql('CREATE INDEX idx_loans_user_status ON loans (user_id, status)');
        $this->addSql('CREATE INDEX idx_loans_status_due ON loans (status, due_at)');
        $this->addSql('COMMENT ON COLUMN loans.borrowed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN loans.due_at IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN loans.returned_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE notifications (id SERIAL NOT NULL, user_id INT NOT NULL, type VARCHAR(30) NOT NULL, message VARCHAR(500) NOT NULL, is_read BOOLEAN DEFAULT false NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, dedupe_key VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6000B0D3A76ED395 ON notifications (user_id)');
        $this->addSql('CREATE INDEX idx_notifications_user_read ON notifications (user_id, is_read)');
        $this->addSql('CREATE UNIQUE INDEX uniq_notifications_user_dedupe ON notifications (user_id, dedupe_key)');
        $this->addSql('COMMENT ON COLUMN notifications.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE penalties (id SERIAL NOT NULL, loan_id INT NOT NULL, paid_by_id INT DEFAULT NULL, amount NUMERIC(10, 2) NOT NULL, days_late INT NOT NULL, paid BOOLEAN DEFAULT false NOT NULL, paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B89F70AFCE73868F ON penalties (loan_id)');
        $this->addSql('CREATE INDEX IDX_B89F70AF7F9BC654 ON penalties (paid_by_id)');
        $this->addSql('CREATE INDEX idx_penalties_paid ON penalties (paid)');
        $this->addSql('COMMENT ON COLUMN penalties.paid_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN penalties.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE system_logs (id SERIAL NOT NULL, actor_id INT DEFAULT NULL, action_type VARCHAR(50) NOT NULL, entity_type VARCHAR(50) NOT NULL, entity_id INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, details JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E14375AA10DAF24A ON system_logs (actor_id)');
        $this->addSql('CREATE INDEX idx_logs_action ON system_logs (action_type)');
        $this->addSql('CREATE INDEX idx_logs_entity ON system_logs (entity_type, entity_id)');
        $this->addSql('CREATE INDEX idx_logs_created ON system_logs (created_at)');
        $this->addSql('COMMENT ON COLUMN system_logs.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE system_settings (setting_key VARCHAR(64) NOT NULL, value VARCHAR(255) NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(setting_key))');
        $this->addSql('COMMENT ON COLUMN system_settings.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE users (id SERIAL NOT NULL, name VARCHAR(120) NOT NULL, email VARCHAR(180) NOT NULL, password_hash VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE INDEX idx_users_role ON users (role)');
        $this->addSql('COMMENT ON COLUMN users.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN users.deleted_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBCA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC16A2B381 FOREIGN KEY (book_id) REFERENCES books (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE penalties ADD CONSTRAINT FK_B89F70AFCE73868F FOREIGN KEY (loan_id) REFERENCES loans (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE penalties ADD CONSTRAINT FK_B89F70AF7F9BC654 FOREIGN KEY (paid_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE system_logs ADD CONSTRAINT FK_E14375AA10DAF24A FOREIGN KEY (actor_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE books ADD CONSTRAINT chk_books_stock_non_negative CHECK (stock >= 0)');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT chk_loans_fee_non_negative CHECK (borrow_fee >= 0)');
        $this->addSql("ALTER TABLE loans ADD CONSTRAINT chk_loans_status CHECK (status IN ('active', 'overdue', 'awaiting_payment', 'closed'))");
        $this->addSql('ALTER TABLE penalties ADD CONSTRAINT chk_penalties_amount_positive CHECK (amount > 0 AND days_late > 0)');
        $this->addSql("ALTER TABLE users ADD CONSTRAINT chk_users_role CHECK (role IN ('student', 'operator', 'super_admin'))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE loans DROP CONSTRAINT FK_82C24DBCA76ED395');
        $this->addSql('ALTER TABLE loans DROP CONSTRAINT FK_82C24DBC16A2B381');
        $this->addSql('ALTER TABLE notifications DROP CONSTRAINT FK_6000B0D3A76ED395');
        $this->addSql('ALTER TABLE penalties DROP CONSTRAINT FK_B89F70AFCE73868F');
        $this->addSql('ALTER TABLE penalties DROP CONSTRAINT FK_B89F70AF7F9BC654');
        $this->addSql('ALTER TABLE system_logs DROP CONSTRAINT FK_E14375AA10DAF24A');
        $this->addSql('DROP TABLE books');
        $this->addSql('DROP TABLE loans');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE penalties');
        $this->addSql('DROP TABLE system_logs');
        $this->addSql('DROP TABLE system_settings');
        $this->addSql('DROP TABLE users');
    }
}
