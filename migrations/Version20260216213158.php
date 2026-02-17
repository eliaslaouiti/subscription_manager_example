<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216213158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'feat: add subscription entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE subscription (id VARCHAR(36) NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME DEFAULT NULL, user_id VARCHAR(36) NOT NULL, product_price_id VARCHAR(36) NOT NULL, PRIMARY KEY (id), FOREIGN KEY (user_id) REFERENCES user (id), FOREIGN KEY (product_price_id) REFERENCES product_price (id))');
        $this->addSql('CREATE INDEX IDX_A3C664D3A76ED395 ON subscription (user_id)');
        $this->addSql('CREATE INDEX IDX_A3C664D31DA4AD70 ON subscription (product_price_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE subscription');
    }
}
