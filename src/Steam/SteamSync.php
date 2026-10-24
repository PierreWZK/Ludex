<?php

namespace App\Steam;

use App\Entity\Achievement;
use App\Entity\CollectionItem;
use App\Entity\Game;
use App\Entity\Platform;
use App\Entity\PlayerAchievement;
use App\Entity\User;
use App\Enum\CollectionStatus;
use App\Reward\RewardEngine;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Importe la bibliothèque Steam d'un joueur : jeux et temps de jeu dans sa collection (plateforme PC),
 * puis, pour chaque jeu joué, le schéma des succès, ceux qu'il a obtenus et leur rareté.
 */
final readonly class SteamSync
{
    public const OK = 'ok';
    public const PENDING = 'en_attente';
    public const PRIVATE_PROFILE = 'profil_prive';
    public const PRIVATE_GAMES = 'details_prives';
    public const ERROR = 'erreur';

    public function __construct(
        private SteamClient $steam,
        private EntityManagerInterface $em,
        private RewardEngine $rewards,
    ) {
    }

    public function sync(User $user): string
    {
        $steamId = $user->getSteamId() ?? throw new \LogicException('Aucun compte Steam lié.');

        $profile = $this->steam->playerSummary($steamId);
        if ($profile) {
            $user->setSteamPersona($profile['personaname'] ?? null)->setSteamAvatar($profile['avatarfull'] ?? null);
        }
        // 3 : profil public ; tout le reste (privé, amis seulement) cache les jeux
        if (3 !== ($profile['communityvisibilitystate'] ?? null)) {
            return $this->finish($user, self::PRIVATE_PROFILE);
        }

        $owned = $this->steam->ownedGames($steamId);
        if (null === $owned) {
            return $this->finish($user, self::PRIVATE_GAMES);
        }

        $pc = $this->em->getRepository(Platform::class)->findOneBy(['slug' => 'pc'])
            ?? throw new \LogicException('La plateforme « pc » manque au catalogue.');

        foreach ($owned as $entry) {
            $game = $this->game((int) $entry['appid'], (string) $entry['name'], $pc);
            $item = $this->item($user, $game, $pc, (int) ($entry['playtime_forever'] ?? 0));
            // shortcut: un appel par jeu joué à chaque synchro ; à limiter aux jeux dont le temps a changé si les bibliothèques deviennent énormes
            if ($item->getPlaytimeMinutes() > 0) {
                $this->achievements($user, $game, $item, $steamId);
            }
        }

        $this->em->flush();
        $this->rewards->evaluate($user);

        return $this->finish($user, self::OK);
    }

    private function finish(User $user, string $status): string
    {
        $user->setSteamSyncStatus($status)->setSteamSyncedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $status;
    }

    /** Le jeu du catalogue : déjà importé, ou du même titre (Elden Ring = ELDEN RING), sinon créé */
    private function game(int $appId, string $name, Platform $pc): Game
    {
        $repository = $this->em->getRepository(Game::class);
        $game = $repository->findOneBy(['steamAppId' => $appId])
            ?? $this->em->createQueryBuilder()
                ->select('g')->from(Game::class, 'g')
                ->where('LOWER(g.title) = :title')->andWhere('g.steamAppId IS NULL')
                ->setParameter('title', mb_strtolower($name))
                ->setMaxResults(1)->getQuery()->getOneOrNullResult();

        if (!$game) {
            $game = (new Game(mb_substr($name, 0, 160)))->setCoverUrl("https://cdn.cloudflare.steamstatic.com/steam/apps/{$appId}/header.jpg");
            $this->em->persist($game);
        }

        return $game->setSteamAppId($appId)->addPlatform($pc);
    }

    private function item(User $user, Game $game, Platform $pc, int $minutes): CollectionItem
    {
        $item = $this->em->getRepository(CollectionItem::class)->findOneBy(['owner' => $user, 'game' => $game, 'platform' => $pc]);
        if (!$item) {
            $item = (new CollectionItem())->setOwner($user)->setGame($game)->setPlatform($pc)
                ->setStatus($minutes > 0 ? CollectionStatus::Playing : CollectionStatus::Backlog);
            $this->em->persist($item);
        }

        // Steam fait foi pour le temps de jeu, sauf si le joueur a noté plus (une autre boutique, par exemple)
        return $item->setFromSteam(true)->setPlaytimeMinutes(max($item->getPlaytimeMinutes(), $minutes));
    }

    private function achievements(User $user, Game $game, CollectionItem $item, string $steamId): void
    {
        // Un jeu tout juste ajouté au catalogue n'est pas encore en base : ni succès connus, ni succès obtenus à relire
        $saved = null !== $game->getId();
        $known = [];
        foreach ($saved ? $this->em->getRepository(Achievement::class)->findBy(['game' => $game]) : [] as $achievement) {
            $known[$achievement->getApiName()] = $achievement;
        }

        // Le schéma ne se charge qu'une fois par jeu, avec la rareté de chaque succès
        if (!$known) {
            $percents = $this->steam->globalPercentages((int) $game->getSteamAppId());
            foreach ($this->steam->achievementSchema((int) $game->getSteamAppId()) as $row) {
                $achievement = (new Achievement($game, $row['name']))
                    ->describe($row['displayName'] ?? $row['name'], $row['description'] ?? null, $row['icon'] ?? null, (bool) ($row['hidden'] ?? false))
                    ->setGlobalPercent($percents[$row['name']] ?? null);
                $this->em->persist($known[$row['name']] = $achievement);
            }
        }
        if (!$known) {
            return;
        }

        $owned = [];
        foreach (!$saved ? [] : $this->em->createQueryBuilder()
            ->select('p')->from(PlayerAchievement::class, 'p')->join('p.achievement', 'a')
            ->where('p.owner = :user')->andWhere('a.game = :game')
            ->setParameter('user', $user)->setParameter('game', $game)
            ->getQuery()->getResult() as $playerAchievement) {
            $owned[$playerAchievement->getAchievement()->getApiName()] = $playerAchievement;
        }

        foreach ($this->steam->playerAchievements($steamId, (int) $game->getSteamAppId()) as $row) {
            if (!$row['achieved'] || isset($owned[$row['apiname']]) || !isset($known[$row['apiname']])) {
                continue;
            }
            $unlockedAt = (new \DateTimeImmutable())->setTimestamp((int) $row['unlocktime'] ?: time());
            $this->em->persist($owned[$row['apiname']] = new PlayerAchievement($user, $known[$row['apiname']], $unlockedAt));
        }

        $dates = array_map(fn (PlayerAchievement $p) => $p->getUnlockedAt(), array_values($owned));
        $last = $dates ? max($dates) : null;
        $item->setAchievementProgress(\count($owned), \count($known), $last);
    }
}
