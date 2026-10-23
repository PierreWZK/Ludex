<?php

namespace App\Steam;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Les appels à la Steam Web API dont Ludex a besoin. La clé reste côté serveur (STEAM_API_KEY).
 * Documentation : https://developer.valvesoftware.com/wiki/Steam_Web_API
 */
final readonly class SteamClient
{
    private const API = 'https://api.steampowered.com';

    public function __construct(
        private HttpClientInterface $http,
        #[Autowire(env: 'STEAM_API_KEY')]
        private string $key,
    ) {
    }

    public function isConfigured(): bool
    {
        return '' !== $this->key;
    }

    /** @return array{steamid: string, personaname?: string, avatarfull?: string, communityvisibilitystate?: int}|null */
    public function playerSummary(string $steamId): ?array
    {
        return $this->get('/ISteamUser/GetPlayerSummaries/v2/', ['steamids' => $steamId])['response']['players'][0] ?? null;
    }

    /**
     * Les jeux possédés ; null quand les « Détails des jeux » du profil ne sont pas publics (Steam répond alors sans liste).
     *
     * @return list<array{appid: int, name: string, playtime_forever: int}>|null
     */
    public function ownedGames(string $steamId): ?array
    {
        $response = $this->get('/IPlayerService/GetOwnedGames/v1/', [
            'steamid' => $steamId,
            'include_appinfo' => 1,
            'include_played_free_games' => 1,
        ])['response'] ?? [];

        if (!isset($response['game_count'])) {
            return null;
        }

        return $response['games'] ?? [];
    }

    /** @return list<array{name: string, displayName: string, description?: string, icon?: string, hidden?: int}> */
    public function achievementSchema(int $appId): array
    {
        return $this->get('/ISteamUserStats/GetSchemaForGame/v2/', ['appid' => $appId, 'l' => 'french'])['game']['availableGameStats']['achievements'] ?? [];
    }

    /**
     * Les succès du joueur pour un jeu ; vide pour un jeu sans statistiques (Steam répond alors en erreur 400).
     *
     * @return list<array{apiname: string, achieved: int, unlocktime: int}>
     */
    public function playerAchievements(string $steamId, int $appId): array
    {
        try {
            return $this->get('/ISteamUserStats/GetPlayerAchievements/v1/', ['steamid' => $steamId, 'appid' => $appId])['playerstats']['achievements'] ?? [];
        } catch (HttpExceptionInterface $e) {
            if (\in_array($e->getResponse()->getStatusCode(), [400, 403], true)) {
                return [];
            }
            throw $e;
        }
    }

    /** @return array<string, float> la part des joueurs qui ont chaque succès, en %, par nom technique */
    public function globalPercentages(int $appId): array
    {
        $rows = $this->get('/ISteamUserStats/GetGlobalAchievementPercentagesForApp/v2/', ['gameid' => $appId], withKey: false)['achievementpercentages']['achievements'] ?? [];

        return array_column(array_map(fn (array $r) => [$r['name'], (float) $r['percent']], $rows), 1, 0);
    }

    private function get(string $path, array $query, bool $withKey = true): array
    {
        if ($withKey) {
            if (!$this->isConfigured()) {
                throw new SteamNotConfigured();
            }
            $query['key'] = $this->key;
        }

        return $this->http->request('GET', self::API.$path, ['query' => $query, 'timeout' => 20])->toArray();
    }
}
