<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251206140058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Insert default admin user';
    }

    public function up(Schema $schema): void
    {
        // Insert default admin user (password: admin)
        $this->addSql("INSERT IGNORE INTO users (name, username, password, level, active) VALUES ('Admin User', 'admin', '21232f297a57a5a743894a0e4a801fc3', 'admin', 1)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM users WHERE username = 'admin'");
    }
}
