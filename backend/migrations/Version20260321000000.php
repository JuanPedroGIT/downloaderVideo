<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial schema: creates the qr_code table.
 */
final class Version20260321000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema: create qr_code table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS qr_code (
            id VARCHAR(20) NOT NULL,
            target_url TEXT NOT NULL,
            clicks INT NOT NULL DEFAULT 0,
            is_active BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql("COMMENT ON COLUMN qr_code.created_at IS '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE qr_code');
    }
}
