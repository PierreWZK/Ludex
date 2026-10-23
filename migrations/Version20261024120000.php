<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** V2 Steam (MySQL 8.4) : compte Steam lié, succès, progression et platines, récompenses, file de synchronisation */
final class Version20261024120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'V2 Steam : comptes liés, succès, platines, récompenses et file Messenger';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform, 'Migration écrite pour MySQL.');

        $this->addSql('ALTER TABLE `user` ADD steam_id VARCHAR(20) DEFAULT NULL, ADD steam_persona VARCHAR(120) DEFAULT NULL, ADD steam_avatar VARCHAR(255) DEFAULT NULL, ADD steam_synced_at DATETIME DEFAULT NULL, ADD steam_sync_status VARCHAR(20) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F3FD4ECA ON `user` (steam_id)');
        $this->addSql('ALTER TABLE game ADD steam_app_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_232B318C4C1A4273 ON game (steam_app_id)');
        $this->addSql('ALTER TABLE collection_item ADD from_steam TINYINT DEFAULT 0 NOT NULL, ADD achievements_unlocked INT DEFAULT 0 NOT NULL, ADD achievements_total INT DEFAULT 0 NOT NULL, ADD platinum_at DATETIME DEFAULT NULL');

        $this->addSql('CREATE TABLE achievement (id INT AUTO_INCREMENT NOT NULL, api_name VARCHAR(128) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, icon VARCHAR(255) DEFAULT NULL, hidden TINYINT DEFAULT 0 NOT NULL, global_percent DOUBLE PRECISION DEFAULT NULL, game_id INT NOT NULL, UNIQUE INDEX UNIQ_96737FF1E48FD9057FD408F5 (game_id, api_name), INDEX IDX_96737FF1E48FD905 (game_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE player_achievement (id INT AUTO_INCREMENT NOT NULL, unlocked_at DATETIME NOT NULL, owner_id INT NOT NULL, achievement_id INT NOT NULL, UNIQUE INDEX UNIQ_CE4496647E3C61F9B3EC99FE (owner_id, achievement_id), INDEX IDX_CE4496647E3C61F9 (owner_id), INDEX IDX_CE449664B3EC99FE (achievement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_reward (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(40) NOT NULL, unlocked_at DATETIME NOT NULL, owner_id INT NOT NULL, UNIQUE INDEX UNIQ_2B83696E7E3C61F977153098 (owner_id, code), INDEX IDX_2B83696E7E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id))');

        $this->addSql('ALTER TABLE achievement ADD CONSTRAINT FK_96737FF1E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE player_achievement ADD CONSTRAINT FK_CE4496647E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE player_achievement ADD CONSTRAINT FK_CE449664B3EC99FE FOREIGN KEY (achievement_id) REFERENCES achievement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_reward ADD CONSTRAINT FK_2B83696E7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE player_achievement');
        $this->addSql('DROP TABLE achievement');
        $this->addSql('DROP TABLE user_reward');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP INDEX UNIQ_8D93D649F3FD4ECA ON `user`');
        $this->addSql('ALTER TABLE `user` DROP steam_id, DROP steam_persona, DROP steam_avatar, DROP steam_synced_at, DROP steam_sync_status');
        $this->addSql('DROP INDEX UNIQ_232B318C4C1A4273 ON game');
        $this->addSql('ALTER TABLE game DROP steam_app_id');
        $this->addSql('ALTER TABLE collection_item DROP from_steam, DROP achievements_unlocked, DROP achievements_total, DROP platinum_at');
    }
}
