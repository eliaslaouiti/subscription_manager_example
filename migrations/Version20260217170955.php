<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260217170955 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'feat: add product and productPrice entities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE product (id VARCHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE product_price (id VARCHAR(36) NOT NULL, price_period VARCHAR(255) NOT NULL, price INTEGER NOT NULL, product_id VARCHAR(36) NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_6B9459854584665A FOREIGN KEY (product_id) REFERENCES product (id))');
        $this->addSql('CREATE INDEX IDX_6B9459854584665A ON product_price (product_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE product_price');
        $this->addSql('DROP TABLE product');
    }
}
