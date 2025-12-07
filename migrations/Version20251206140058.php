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
        // Insert default admin user (password: admin, bcrypt hashed)
        $this->addSql("INSERT IGNORE INTO users (name, username, password, level, active) VALUES ('Admin User', 'admin', '\$2y\$12\$z.T5wBhT5.fDa65N3QFqxeiqfqKSF8e1whq7KsKsxR4njl1FMEhIW', 'admin', 1)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM users WHERE username = 'admin'");
    }
}
