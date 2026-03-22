<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create admin_user table for QR panel authentication';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS admin_user (
            id SERIAL NOT NULL,
            username VARCHAR(64) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_ADMIN_USERNAME ON admin_user (username)');
        $this->addSql("COMMENT ON COLUMN admin_user.created_at IS '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE admin_user');
    }
}
