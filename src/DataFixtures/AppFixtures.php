<?php

namespace App\DataFixtures;

use App\Demo\DemoAccount;
use App\Entity\Game;
use App\Entity\Genre;
use App\Entity\Platform;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Le catalogue de démonstration (6 plateformes, 13 genres, 54 jeux), puis le compte de démo
 * avec sa collection et sa liste d'envies (voir DemoAccount).
 */
final class AppFixtures extends Fixture
{
    public const DEMO_EMAIL = DemoAccount::EMAIL;
    public const DEMO_PASSWORD = DemoAccount::PASSWORD;

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

    public function __construct(private readonly DemoAccount $demo)
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

        foreach (self::GAMES as [$title, $year, $platformSlugs, $genreSlugs]) {
            $game = new Game($title, $year);
            foreach ($platformSlugs as $slug) {
                $game->addPlatform($platforms[$slug]);
            }
            foreach ($genreSlugs as $slug) {
                $game->addGenre($genres[$slug]);
            }
            $manager->persist($game);
        }

        $manager->flush();

        $this->demo->reset();
    }
}
