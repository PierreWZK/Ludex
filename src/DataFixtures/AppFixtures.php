<?php

namespace App\DataFixtures;

use App\Entity\CollectionItem;
use App\Entity\Game;
use App\Entity\Genre;
use App\Entity\Platform;
use App\Entity\User;
use App\Entity\WishlistItem;
use App\Enum\CollectionStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Le catalogue de démonstration (6 plateformes, 13 genres, 54 jeux) et le compte de démo,
 * avec une collection et une liste d'envies déjà remplies.
 */
final class AppFixtures extends Fixture
{
    public const DEMO_EMAIL = 'demo@ludex.fr';
    public const DEMO_PASSWORD = 'ludex-demo';

    private const PLATFORMS = [
        'pc' => ['PC', 'Divers'],
        'ps5' => ['PlayStation 5', 'Sony'],
        'ps4' => ['PlayStation 4', 'Sony'],
        'xbox' => ['Xbox Series X|S', 'Microsoft'],
        'switch' => ['Nintendo Switch', 'Nintendo'],
        'switch-2' => ['Nintendo Switch 2', 'Nintendo'],
    ];

    private const GENRES = [
        'action' => 'Action', 'aventure' => 'Aventure', 'rpg' => 'RPG', 'course' => 'Course', 'sport' => 'Sport',
        'strategie' => 'Stratégie', 'plates-formes' => 'Plates-formes', 'tir' => 'Tir', 'simulation' => 'Simulation',
        'combat' => 'Combat', 'reflexion' => 'Réflexion', 'horreur' => 'Horreur', 'roguelike' => 'Roguelike',
    ];

    // [titre, année, plateformes, genres]
    private const GAMES = [
        ['The Legend of Zelda: Breath of the Wild', 2017, ['switch'], ['aventure', 'action']],
        ['The Legend of Zelda: Tears of the Kingdom', 2023, ['switch', 'switch-2'], ['aventure', 'action']],
        ['Elden Ring', 2022, ['pc', 'ps5', 'ps4', 'xbox'], ['rpg', 'action']],
        ["Baldur's Gate 3", 2023, ['pc', 'ps5', 'xbox'], ['rpg', 'strategie']],
        ['The Witcher 3: Wild Hunt', 2015, ['pc', 'ps5', 'ps4', 'xbox', 'switch'], ['rpg', 'aventure']],
        ['Cyberpunk 2077', 2020, ['pc', 'ps5', 'xbox', 'switch-2'], ['rpg', 'tir']],
        ['Hades', 2020, ['pc', 'ps5', 'ps4', 'xbox', 'switch'], ['roguelike', 'action']],
        ['Hades II', 2025, ['pc', 'switch', 'switch-2'], ['roguelike', 'action']],
        ['Hollow Knight', 2017, ['pc', 'ps4', 'xbox', 'switch'], ['plates-formes', 'action']],
        ['Celeste', 2018, ['pc', 'ps4', 'xbox', 'switch'], ['plates-formes']],
        ['Stardew Valley', 2016, ['pc', 'ps4', 'xbox', 'switch'], ['simulation']],
        ['Red Dead Redemption 2', 2018, ['pc', 'ps4', 'xbox'], ['action', 'aventure']],
        ['God of War Ragnarök', 2022, ['ps5', 'ps4', 'pc'], ['action', 'aventure']],
        ["Marvel's Spider-Man 2", 2023, ['ps5', 'pc'], ['action', 'aventure']],
        ['Super Mario Odyssey', 2017, ['switch'], ['plates-formes']],
        ['Mario Kart 8 Deluxe', 2017, ['switch'], ['course']],
        ['Mario Kart World', 2025, ['switch-2'], ['course']],
        ['Forza Horizon 5', 2021, ['pc', 'xbox', 'ps5'], ['course']],
        ['Gran Turismo 7', 2022, ['ps5', 'ps4'], ['course', 'simulation']],
        ['EA Sports FC 25', 2024, ['pc', 'ps5', 'ps4', 'xbox', 'switch'], ['sport']],
        ['Rocket League', 2015, ['pc', 'ps4', 'xbox', 'switch'], ['sport', 'course']],
        ['Street Fighter 6', 2023, ['pc', 'ps5', 'ps4', 'xbox'], ['combat']],
        ['Tekken 8', 2024, ['pc', 'ps5', 'xbox'], ['combat']],
        ['Super Smash Bros. Ultimate', 2018, ['switch'], ['combat']],
        ["Sid Meier's Civilization VI", 2016, ['pc', 'ps4', 'xbox', 'switch'], ['strategie']],
        ['Age of Empires II: Definitive Edition', 2019, ['pc', 'xbox', 'ps5'], ['strategie']],
        ['Portal 2', 2011, ['pc', 'switch'], ['reflexion']],
        ['The Talos Principle 2', 2023, ['pc', 'ps5', 'xbox'], ['reflexion']],
        ['Resident Evil 4', 2023, ['pc', 'ps5', 'ps4', 'xbox'], ['horreur', 'action']],
        ['Alan Wake 2', 2023, ['pc', 'ps5', 'xbox'], ['horreur', 'aventure']],
        ['Dead Space', 2023, ['pc', 'ps5', 'xbox'], ['horreur', 'tir']],
        ['DOOM Eternal', 2020, ['pc', 'ps4', 'xbox', 'switch'], ['tir', 'action']],
        ['Halo Infinite', 2021, ['pc', 'xbox'], ['tir']],
        ['Call of Duty: Black Ops 6', 2024, ['pc', 'ps5', 'ps4', 'xbox'], ['tir']],
        ['Minecraft', 2011, ['pc', 'ps4', 'xbox', 'switch'], ['simulation', 'aventure']],
        ['Animal Crossing: New Horizons', 2020, ['switch'], ['simulation']],
        ['Final Fantasy VII Rebirth', 2024, ['ps5', 'pc'], ['rpg', 'action']],
        ['Persona 5 Royal', 2019, ['ps4', 'ps5', 'pc', 'xbox', 'switch'], ['rpg']],
        ['Dragon Quest XI S', 2019, ['switch', 'pc', 'ps4', 'xbox'], ['rpg']],
        ['Astro Bot', 2024, ['ps5'], ['plates-formes']],
        ['It Takes Two', 2021, ['pc', 'ps5', 'ps4', 'xbox', 'switch'], ['aventure', 'plates-formes']],
        ['Split Fiction', 2025, ['pc', 'ps5', 'xbox', 'switch-2'], ['aventure', 'action']],
        ['Clair Obscur: Expedition 33', 2025, ['pc', 'ps5', 'xbox'], ['rpg']],
        ['Balatro', 2024, ['pc', 'ps5', 'ps4', 'xbox', 'switch'], ['roguelike', 'strategie']],
        ['Dead Cells', 2018, ['pc', 'ps4', 'xbox', 'switch'], ['roguelike', 'action']],
        ['Slay the Spire', 2019, ['pc', 'ps4', 'xbox', 'switch'], ['roguelike', 'strategie']],
        ['Disco Elysium', 2019, ['pc', 'ps5', 'ps4', 'xbox', 'switch'], ['rpg']],
        ['Outer Wilds', 2019, ['pc', 'ps5', 'ps4', 'xbox', 'switch'], ['aventure', 'reflexion']],
        ['Sekiro: Shadows Die Twice', 2019, ['pc', 'ps4', 'xbox'], ['action']],
        ['Ghost of Tsushima', 2020, ['ps5', 'ps4', 'pc'], ['action', 'aventure']],
        ['Death Stranding', 2019, ['ps5', 'ps4', 'pc'], ['aventure']],
        ['Pokémon Écarlate', 2022, ['switch'], ['rpg']],
        ['Kingdom Come: Deliverance II', 2025, ['pc', 'ps5', 'xbox'], ['rpg']],
        ['Metroid Prime 4: Beyond', 2025, ['switch', 'switch-2'], ['action', 'aventure']],
    ];

    // La collection du compte de démo : [titre, plateforme, statut, heures, note]
    private const DEMO_COLLECTION = [
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

    // [titre, priorité 1 à 3, note]
    private const DEMO_WISHLIST = [
        ['Metroid Prime 4: Beyond', 1, 'Dès sa sortie en version Switch 2'],
        ['Kingdom Come: Deliverance II', 2, null],
        ['Split Fiction', 1, 'À faire à deux'],
        ['Ghost of Tsushima', 3, 'Attendre une promotion'],
        ['Slay the Spire', 2, null],
    ];

    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $platforms = [];
        foreach (self::PLATFORMS as $slug => [$name, $maker]) {
            $manager->persist($platforms[$slug] = new Platform($name, $slug, $maker));
        }

        $genres = [];
        foreach (self::GENRES as $slug => $name) {
            $manager->persist($genres[$slug] = new Genre($name, $slug));
        }

        $games = [];
        foreach (self::GAMES as [$title, $year, $platformSlugs, $genreSlugs]) {
            $game = new Game($title, $year);
            foreach ($platformSlugs as $slug) {
                $game->addPlatform($platforms[$slug]);
            }
            foreach ($genreSlugs as $slug) {
                $game->addGenre($genres[$slug]);
            }
            $manager->persist($games[$title] = $game);
        }

        $demo = (new User())->setEmail(self::DEMO_EMAIL);
        $demo->setPassword($this->hasher->hashPassword($demo, self::DEMO_PASSWORD));
        $manager->persist($demo);

        foreach (self::DEMO_COLLECTION as $i => [$title, $platform, $status, $hours, $rating]) {
            $item = (new CollectionItem())
                ->setOwner($demo)
                ->setGame($games[$title])
                ->setPlatform($platforms[$platform])
                ->setStatus($status)
                ->setPlaytimeMinutes($hours * 60)
                ->setRating($rating)
                ->setAddedAt(new \DateTimeImmutable(sprintf('-%d days', 400 - $i * 20)));
            if (CollectionStatus::Finished === $status) {
                $item->setFinishedAt(new \DateTimeImmutable(sprintf('-%d days', 380 - $i * 20)));
            }
            $manager->persist($item);
        }

        foreach (self::DEMO_WISHLIST as [$title, $priority, $note]) {
            $manager->persist((new WishlistItem())->setOwner($demo)->setGame($games[$title])->setPriority($priority)->setNote($note));
        }

        $manager->flush();
    }
}
