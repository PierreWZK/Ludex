<?php

namespace App\Tests;

use App\Entity\CollectionItem;
use App\Entity\Game;
use App\Entity\User;
use App\Tests\Steam\FakeSteam;

/** Steam : liaison par OpenID, synchronisation, succès à faire, platines et récompenses (contre un faux Steam) */
final class SteamTest extends LudexTestCase
{
    private function linkSteam(string $email, string $steamId): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        $user->setSteamId($steamId);
        $this->em->flush();
        $this->em->clear();
    }

    /** Le retour de Steam après connexion, comme le navigateur le recevrait */
    private function steamReturns(string $linkUrl, string $steamId, ?string $state = null): void
    {
        parse_str((string) parse_url($linkUrl, \PHP_URL_QUERY), $query);
        $returnTo = $query['openid_return_to'];
        parse_str((string) parse_url($returnTo, \PHP_URL_QUERY), $returnQuery);

        $openId = http_build_query([
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'id_res',
            'openid.op_endpoint' => 'https://steamcommunity.com/openid/login',
            'openid.claimed_id' => "https://steamcommunity.com/openid/id/$steamId",
            'openid.identity' => "https://steamcommunity.com/openid/id/$steamId",
            'openid.return_to' => $returnTo,
            'openid.response_nonce' => '2026-10-23T12:00:00Zabc',
            'openid.assoc_handle' => '1234567890',
            'openid.signed' => 'signed,op_endpoint,claimed_id,identity,return_to,response_nonce,assoc_handle',
            'openid.sig' => 'c2lnbmF0dXJl',
        ]);
        self::createClient()->request('GET', '/steam/callback?state='.urlencode($state ?? $returnQuery['state']).'&'.$openId);
    }

    public function testLinkingProvesTheSteamAccount(): void
    {
        $bob = $this->bob();
        $link = $bob->request('POST', '/api/account/steam/link')->toArray()['linkUrl'];
        self::assertStringStartsWith('https://steamcommunity.com/openid/login?', $link);

        $this->steamReturns($link, FakeSteam::PUBLIC);
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Compte Steam lié', self::getClient()->getResponse()->getContent());

        $account = $bob->request('GET', '/api/account/steam')->toArray();
        self::assertSame([true, FakeSteam::PUBLIC, 'Joueur 1'], [$account['linked'], $account['steamId'], $account['persona']]);
    }

    public function testATamperedStateIsRefused(): void
    {
        $link = $this->bob()->request('POST', '/api/account/steam/link')->toArray()['linkUrl'];
        $this->steamReturns($link, FakeSteam::PUBLIC, '1.9999999999.'.str_repeat('0', 64));

        self::assertResponseStatusCodeSame(400);
    }

    public function testASteamAccountLinksToOnlyOneLudexAccount(): void
    {
        $this->linkSteam('bob@ludex.fr', FakeSteam::PUBLIC);
        self::createClient()->request('POST', '/api/account', ['json' => ['email' => 'carla@ludex.fr', 'plainPassword' => 'carla-password']]);
        $link = $this->as('carla@ludex.fr', 'carla-password')->request('POST', '/api/account/steam/link')->toArray()['linkUrl'];
        $this->steamReturns($link, FakeSteam::PUBLIC);

        self::assertResponseStatusCodeSame(409);
    }

    /** Un jeu dont Steam refuse les succès (403, 500) est importé sans bloquer la synchro */
    public function testSyncImportsGamesAchievementsPlatinumsAndRewards(): void
    {
        $this->linkSteam('bob@ludex.fr', FakeSteam::PUBLIC);
        $bob = $this->bob();

        $bob->request('POST', '/api/account/steam/sync');
        self::assertResponseStatusCodeSame(202);
        self::assertJsonContains(['syncStatus' => 'ok']);

        // Les jeux du catalogue sont retrouvés par leur titre, pas recréés
        self::assertSame(1, $this->em->getRepository(Game::class)->count(['title' => 'Celeste']));
        $items = $bob->request('GET', '/api/collection_items?order[playtimeMinutes]=desc')->toArray()['member'];
        self::assertSame(['Hades', 'Celeste', 'Un jeu sans succès', 'Un jeu jamais lancé'], array_column(array_column($items, 'game'), 'title'));
        self::assertSame([3840, 600, 30, 0], array_column($items, 'playtimeMinutes'));
        self::assertSame([100, 66.7], [$items[0]['achievementsPercent'], $items[1]['achievementsPercent']]);

        // Succès à faire : celui qui manque à Celeste
        $celeste = $items[1]['@id'];
        $todo = $bob->request('GET', "$celeste/achievements?state=todo")->toArray()['member'];
        self::assertSame(['Face C'], array_column($todo, 'name'));
        $done = $bob->request('GET', "$celeste/achievements?state=done")->toArray()['member'];
        self::assertSame(['Cœur de cristal', 'Premier sommet'], array_column($done, 'name'), 'les plus récents d\'abord');

        // Platine : Hades, daté de son dernier succès
        $platinums = $bob->request('GET', '/api/collection_items?exists[platinumAt]=true')->toArray()['member'];
        self::assertSame(['Hades'], array_column(array_column($platinums, 'game'), 'title'));
        self::assertStringStartsWith('2024-10-27', $platinums[0]['platinumAt']);

        $bob->request('GET', '/api/account/stats');
        self::assertJsonContains(['achievements' => 4, 'platinums' => 1, 'games' => 4]);

        $rewards = array_column($bob->request('GET', '/api/account/rewards')->toArray()['member'], 'unlocked', 'code');
        self::assertTrue($rewards['premier-succes']);
        self::assertTrue($rewards['platine']);
        self::assertTrue($rewards['rarissime'], 'Chaleur extrême : 0,8 % des joueurs');
        self::assertFalse($rewards['cent-succes']);
    }

    public function testSyncIsLimitedToOncePerHour(): void
    {
        $this->linkSteam('bob@ludex.fr', FakeSteam::PUBLIC);
        $bob = $this->bob();
        $bob->request('POST', '/api/account/steam/sync');
        $bob->request('POST', '/api/account/steam/sync');

        self::assertResponseStatusCodeSame(429);
    }

    public function testAPrivateProfileExplainsWhatToChange(): void
    {
        $this->linkSteam('bob@ludex.fr', FakeSteam::PRIVATE_PROFILE);
        $response = $this->bob()->request('POST', '/api/account/steam/sync');

        self::assertJsonContains(['syncStatus' => 'profil_prive']);
        self::assertStringContainsString('Public', $response->toArray()['message']);
    }

    public function testHiddenGameDetailsExplainWhatToChange(): void
    {
        $this->linkSteam('bob@ludex.fr', FakeSteam::PRIVATE_GAMES);
        $this->bob()->request('POST', '/api/account/steam/sync');

        self::assertJsonContains(['syncStatus' => 'details_prives']);
        self::assertSame(0, $this->em->getRepository(CollectionItem::class)->count([]) - 18, 'rien d\'importé');
    }

    public function testAnotherPlayerCannotReadTheAchievements(): void
    {
        $this->linkSteam('bob@ludex.fr', FakeSteam::PUBLIC);
        $this->bob()->request('POST', '/api/account/steam/sync');
        $bob = $this->em->getRepository(User::class)->findOneBy(['email' => 'bob@ludex.fr']);
        $item = self::findIriBy(CollectionItem::class, ['owner' => $bob]);

        $this->demo()->request('GET', "$item/achievements");

        self::assertResponseStatusCodeSame(404);
    }

    public function testSyncNeedsALinkedAccount(): void
    {
        $this->bob()->request('POST', '/api/account/steam/sync');

        self::assertResponseStatusCodeSame(409);
    }
}
