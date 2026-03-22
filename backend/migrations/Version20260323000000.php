<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_id FK to qr_code table for per-user QR ownership';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE qr_code ADD COLUMN user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE qr_code ADD CONSTRAINT fk_qr_code_user FOREIGN KEY (user_id) REFERENCES admin_user(id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_qr_code_user_id ON qr_code (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_qr_code_user_id');
        $this->addSql('ALTER TABLE qr_code DROP CONSTRAINT IF EXISTS fk_qr_code_user');
        $this->addSql('ALTER TABLE qr_code DROP COLUMN user_id');
    }
}
