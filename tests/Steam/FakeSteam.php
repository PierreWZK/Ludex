<?php

namespace App\Tests\Steam;

use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Un faux Steam pour les tests : réponses au format de la Steam Web API et de l'OpenID de Steam.
 *
 * Trois joueurs : …001 public (Celeste, 2 succès sur 3 ; Hades, tout obtenu), …002 au profil privé,
 * …003 au profil public mais aux détails des jeux masqués.
 */
final class FakeSteam
{
    public const PUBLIC = '76561198000000001';
    public const PRIVATE_PROFILE = '76561198000000002';
    public const PRIVATE_GAMES = '76561198000000003';

    private const CELESTE = 504230;
    private const HADES = 1145360;
    /** Un jeu sans succès : Steam refuse sa rareté (403) et plante sur les succès du joueur (500) */
    private const NO_STATS = 1000;

    public function __invoke(string $method, string $url, array $options = []): MockResponse
    {
        $path = parse_url($url, \PHP_URL_PATH);
        parse_str((string) parse_url($url, \PHP_URL_QUERY), $query);

        if ('/openid/login' === $path) {
            return new MockResponse("ns:http://specs.openid.net/auth/2.0\nis_valid:true\n");
        }

        $appId = (int) ($query['appid'] ?? $query['gameid'] ?? 0);
        if (self::NO_STATS === $appId && !str_contains($path, 'GetSchemaForGame')) {
            return new MockResponse('{}', ['http_code' => str_contains($path, 'GetPlayerAchievements') ? 500 : 403]);
        }

        $json = match (true) {
            str_contains($path, 'GetPlayerSummaries') => ['response' => ['players' => [[
                'steamid' => $query['steamids'],
                'personaname' => 'Joueur '.substr($query['steamids'], -1),
                'avatarfull' => 'https://avatars.steamstatic.com/test_full.jpg',
                'communityvisibilitystate' => self::PRIVATE_PROFILE === $query['steamids'] ? 1 : 3,
            ]]]],
            str_contains($path, 'GetOwnedGames') => self::PUBLIC === $query['steamid']
                ? ['response' => ['game_count' => 4, 'games' => [
                    ['appid' => self::CELESTE, 'name' => 'Celeste', 'playtime_forever' => 600],
                    ['appid' => self::HADES, 'name' => 'Hades', 'playtime_forever' => 3840],
                    ['appid' => self::NO_STATS, 'name' => 'Un jeu sans succès', 'playtime_forever' => 30],
                    ['appid' => 999, 'name' => 'Un jeu jamais lancé', 'playtime_forever' => 0],
                ]]]
                : ['response' => []],
            str_contains($path, 'GetSchemaForGame') => ['game' => ['availableGameStats' => ['achievements' => match ((int) $query['appid']) {
                self::CELESTE => [
                    ['name' => 'A', 'displayName' => 'Premier sommet', 'description' => 'Finir le chapitre 1', 'icon' => 'https://cdn.test/a.jpg', 'hidden' => 0],
                    ['name' => 'B', 'displayName' => 'Cœur de cristal', 'description' => 'Trouver un cœur', 'icon' => 'https://cdn.test/b.jpg', 'hidden' => 0],
                    ['name' => 'C', 'displayName' => 'Face C', 'icon' => 'https://cdn.test/c.jpg', 'hidden' => 1],
                ],
                self::HADES => [
                    ['name' => 'X', 'displayName' => 'Sortir des Enfers', 'icon' => 'https://cdn.test/x.jpg', 'hidden' => 0],
                    ['name' => 'Y', 'displayName' => 'Chaleur extrême', 'icon' => 'https://cdn.test/y.jpg', 'hidden' => 0],
                ],
                default => [],
            }]]],
            str_contains($path, 'GetPlayerAchievements') => ['playerstats' => ['success' => true, 'achievements' => match ((int) $query['appid']) {
                self::CELESTE => [
                    ['apiname' => 'A', 'achieved' => 1, 'unlocktime' => 1700000000],
                    ['apiname' => 'B', 'achieved' => 1, 'unlocktime' => 1710000000],
                    ['apiname' => 'C', 'achieved' => 0, 'unlocktime' => 0],
                ],
                self::HADES => [
                    ['apiname' => 'X', 'achieved' => 1, 'unlocktime' => 1720000000],
                    ['apiname' => 'Y', 'achieved' => 1, 'unlocktime' => 1730000000],
                ],
                default => [],
            }]],
            str_contains($path, 'GetGlobalAchievementPercentagesForApp') => ['achievementpercentages' => ['achievements' => match ((int) $query['gameid']) {
                self::CELESTE => [['name' => 'A', 'percent' => '80.5'], ['name' => 'B', 'percent' => '30.1'], ['name' => 'C', 'percent' => '2.4']],
                self::HADES => [['name' => 'X', 'percent' => '50'], ['name' => 'Y', 'percent' => '0.8']],
                default => [],
            }]],
            default => throw new \LogicException("Appel Steam inattendu : $url"),
        };

        return new MockResponse(json_encode($json), ['response_headers' => ['content-type' => 'application/json']]);
    }
}
