<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Comptes (pseudo, date d'inscription) et jeu du jour (MySQL 8.4) */
final class Version20261024150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Comptes : pseudo et date d\'inscription ; jeu du jour';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform, 'Migration écrite pour MySQL.');

        $this->addSql('ALTER TABLE `user` ADD display_name VARCHAR(40) DEFAULT NULL, ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('CREATE TABLE daily_puzzle (id INT AUTO_INCREMENT NOT NULL, day VARCHAR(10) NOT NULL, game_id INT NOT NULL, UNIQUE INDEX UNIQ_A398E873E5A02990 (day), INDEX IDX_A398E873E48FD905 (game_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE daily_puzzle ADD CONSTRAINT FK_A398E873E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE');
        // Le compte de démo existe déjà : il reçoit son nom
        $this->addSql("UPDATE `user` SET display_name = 'Joueur de démo' WHERE email = 'demo@ludex.fr'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE daily_puzzle');
        $this->addSql('ALTER TABLE `user` DROP display_name, DROP created_at');
    }
}
