<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Schéma initial de Ludex (MySQL 8.4) : catalogue, comptes, collections et listes d'envies */
final class Version20261024090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Schéma initial : plateformes, genres, jeux, comptes, collection et liste d'envies";
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform, "Migration écrite pour MySQL.");
        $this->addSql('CREATE TABLE collection_item (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(255) NOT NULL, playtime_minutes INT NOT NULL, rating INT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, added_at DATETIME NOT NULL, finished_at DATETIME DEFAULT NULL, owner_id INT NOT NULL, game_id INT NOT NULL, platform_id INT NOT NULL, UNIQUE INDEX UNIQ_556C09F07E3C61F9E48FD905FFE6496F (owner_id, game_id, platform_id), INDEX IDX_556C09F07E3C61F9 (owner_id), INDEX IDX_556C09F0E48FD905 (game_id), INDEX IDX_556C09F0FFE6496F (platform_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE game (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(160) NOT NULL, release_year INT DEFAULT NULL, cover_url VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE game_platform (game_id INT NOT NULL, platform_id INT NOT NULL, INDEX IDX_92162FEDE48FD905 (game_id), INDEX IDX_92162FEDFFE6496F (platform_id), PRIMARY KEY (game_id, platform_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE game_genre (game_id INT NOT NULL, genre_id INT NOT NULL, INDEX IDX_B1634A77E48FD905 (game_id), INDEX IDX_B1634A774296D31F (genre_id), PRIMARY KEY (game_id, genre_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE genre (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(60) NOT NULL, slug VARCHAR(60) NOT NULL, UNIQUE INDEX UNIQ_835033F8989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE platform (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(80) NOT NULL, slug VARCHAR(80) NOT NULL, maker VARCHAR(80) NOT NULL, UNIQUE INDEX UNIQ_3952D0CB989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE wishlist_item (id INT AUTO_INCREMENT NOT NULL, priority SMALLINT NOT NULL, note VARCHAR(500) DEFAULT NULL, added_at DATETIME NOT NULL, owner_id INT NOT NULL, game_id INT NOT NULL, UNIQUE INDEX UNIQ_6424F4E87E3C61F9E48FD905 (owner_id, game_id), INDEX IDX_6424F4E87E3C61F9 (owner_id), INDEX IDX_6424F4E8E48FD905 (game_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE collection_item ADD CONSTRAINT FK_556C09F07E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE collection_item ADD CONSTRAINT FK_556C09F0E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE collection_item ADD CONSTRAINT FK_556C09F0FFE6496F FOREIGN KEY (platform_id) REFERENCES platform (id)');
        $this->addSql('ALTER TABLE game_platform ADD CONSTRAINT FK_92162FEDE48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_platform ADD CONSTRAINT FK_92162FEDFFE6496F FOREIGN KEY (platform_id) REFERENCES platform (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_genre ADD CONSTRAINT FK_B1634A77E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_genre ADD CONSTRAINT FK_B1634A774296D31F FOREIGN KEY (genre_id) REFERENCES genre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wishlist_item ADD CONSTRAINT FK_6424F4E87E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wishlist_item ADD CONSTRAINT FK_6424F4E8E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("SET FOREIGN_KEY_CHECKS = 0");
        $this->addSql("DROP TABLE collection_item");
        $this->addSql("DROP TABLE wishlist_item");
        $this->addSql("DROP TABLE game_platform");
        $this->addSql("DROP TABLE game_genre");
        $this->addSql("DROP TABLE game");
        $this->addSql("DROP TABLE genre");
        $this->addSql("DROP TABLE platform");
        $this->addSql("DROP TABLE `user`");
        $this->addSql("SET FOREIGN_KEY_CHECKS = 1");
    }
}
