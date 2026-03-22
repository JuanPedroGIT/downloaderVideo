<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email, verification and password-reset token columns to admin_user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE admin_user ADD COLUMN email VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE admin_user ADD COLUMN is_verified BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE admin_user ADD COLUMN verification_token VARCHAR(100) DEFAULT NULL');
        $this->addSql("ALTER TABLE admin_user ADD COLUMN verification_token_expires TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL");
        $this->addSql('ALTER TABLE admin_user ADD COLUMN reset_token VARCHAR(100) DEFAULT NULL');
        $this->addSql("ALTER TABLE admin_user ADD COLUMN reset_token_expires TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL");
        $this->addSql("COMMENT ON COLUMN admin_user.verification_token_expires IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN admin_user.reset_token_expires IS '(DC2Type:datetime_immutable)'");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ADMIN_EMAIL ON admin_user (email) WHERE email IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS UNIQ_ADMIN_EMAIL');
        $this->addSql('ALTER TABLE admin_user DROP COLUMN IF EXISTS email');
        $this->addSql('ALTER TABLE admin_user DROP COLUMN IF EXISTS is_verified');
        $this->addSql('ALTER TABLE admin_user DROP COLUMN IF EXISTS verification_token');
        $this->addSql('ALTER TABLE admin_user DROP COLUMN IF EXISTS verification_token_expires');
        $this->addSql('ALTER TABLE admin_user DROP COLUMN IF EXISTS reset_token');
        $this->addSql('ALTER TABLE admin_user DROP COLUMN IF EXISTS reset_token_expires');
    }
}
