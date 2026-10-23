<?php

namespace App\Demo;

use App\Entity\CollectionItem;
use App\Entity\Game;
use App\Entity\Platform;
use App\Entity\PlayerAchievement;
use App\Entity\User;
use App\Entity\UserReward;
use App\Entity\WishlistItem;
use App\Enum\CollectionStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Le compte de démo, partagé par tous les visiteurs : chacun peut modifier sa collection, et tout revient
 * à l'état d'origine chaque nuit à minuit (heure de Paris).
 */
final readonly class DemoAccount
{
    public const EMAIL = 'demo@ludex.fr';
    public const PASSWORD = 'ludex-demo';
    public const NAME = 'Joueur de démo';

    // La collection : [titre, plateforme, statut, heures, note]
    private const COLLECTION = [
        ['Elden Ring', 'ps5', CollectionStatus::Finished, 142, 10],
        ["Baldur's Gate 3", 'pc', CollectionStatus::Finished, 118, 10],
        ['Hades', 'switch', CollectionStatus::Finished, 64, 9],
        ['Hades II', 'switch-2', CollectionStatus::Playing, 21, null],
        ['The Legend of Zelda: Tears of the Kingdom', 'switch', CollectionStatus::Finished, 96, 9],
        ['Hollow Knight', 'switch', CollectionStatus::Abandoned, 17, 7],
        ['Celeste', 'pc', CollectionStatus::Finished, 12, 9],
        ['Stardew Valley', 'pc', CollectionStatus::Playing, 55, 8],
        ['Cyberpunk 2077', 'pc', CollectionStatus::Finished, 73, 8],
        ['Mario Kart World', 'switch-2', CollectionStatus::Playing, 14, 8],
        ['Astro Bot', 'ps5', CollectionStatus::Finished, 15, 9],
        ['Final Fantasy VII Rebirth', 'ps5', CollectionStatus::Backlog, 0, null],
        ['Clair Obscur: Expedition 33', 'ps5', CollectionStatus::Playing, 31, null],
        ['Balatro', 'pc', CollectionStatus::Playing, 48, 9],
        ['Outer Wilds', 'pc', CollectionStatus::Backlog, 0, null],
        ['Disco Elysium', 'pc', CollectionStatus::Backlog, 2, null],
        ['Street Fighter 6', 'ps5', CollectionStatus::Abandoned, 9, 6],
        ['Forza Horizon 5', 'xbox', CollectionStatus::Finished, 40, 8],
    ];

    // La liste d'envies : [titre, priorité 1 à 3, note]
    private const WISHLIST = [
        ['Metroid Prime 4: Beyond', 1, 'Dès sa sortie en version Switch 2'],
        ['Kingdom Come: Deliverance II', 2, null],
        ['Split Fiction', 1, 'À faire à deux'],
        ['Ghost of Tsushima', 3, 'Attendre une promotion'],
        ['Slay the Spire', 2, null],
    ];

    public function __construct(private EntityManagerInterface $em, private UserPasswordHasherInterface $hasher)
    {
    }

    /** Remet le compte de démo à zéro (et le crée s'il manque). Le catalogue doit déjà être en base. */
    public function reset(): User
    {
        $demo = $this->em->getRepository(User::class)->findOneBy(['email' => self::EMAIL]);
        if (!$demo) {
            $this->em->persist($demo = (new User())->setEmail(self::EMAIL));
        }
        $demo->setDisplayName(self::NAME)->setRoles([])
            ->setSteamId(null)->setSteamPersona(null)->setSteamAvatar(null)->setSteamSyncedAt(null)->setSteamSyncStatus(null);
        $demo->setPassword($this->hasher->hashPassword($demo, self::PASSWORD));
        $this->em->flush();

        foreach ([CollectionItem::class, WishlistItem::class, UserReward::class, PlayerAchievement::class] as $class) {
            $this->em->createQuery("DELETE FROM $class x WHERE x.owner = :demo")->setParameter('demo', $demo)->execute();
        }

        $games = $this->em->getRepository(Game::class);
        $platforms = $this->em->getRepository(Platform::class);
        $now = new \DateTimeImmutable();
        foreach (self::COLLECTION as $i => [$title, $platform, $status, $hours, $rating]) {
            $game = $games->findOneBy(['title' => $title]);
            $support = $platforms->findOneBy(['slug' => $platform]);
            if (!$game || !$support) {
                continue;
            }
            // Des ajouts étalés sur cinq mois, pour que la collection se lise comme un journal
            $item = (new CollectionItem())->setOwner($demo)->setGame($game)->setPlatform($support)
                ->setStatus($status)->setPlaytimeMinutes($hours * 60)->setRating($rating)
                ->setAddedAt($now->modify(sprintf('-%d days', 150 - $i * 8)));
            if (CollectionStatus::Finished === $status) {
                $item->setFinishedAt($now->modify(sprintf('-%d days', 144 - $i * 8)));
            }
            $this->em->persist($item);
        }

        foreach (self::WISHLIST as [$title, $priority, $note]) {
            if ($game = $games->findOneBy(['title' => $title])) {
                $this->em->persist((new WishlistItem())->setOwner($demo)->setGame($game)->setPriority($priority)->setNote($note));
            }
        }

        $this->em->flush();

        return $demo;
    }
}
