<?php

namespace App\Tests;

use App\Demo\DemoAccount;
use App\Entity\CollectionItem;
use App\Entity\Game;
use App\Entity\Platform;
use App\Entity\User;

/** Le compte : inscription, profil, mot de passe, suppression ; et le compte de démo, protégé et remis à zéro */
final class AccountTest extends LudexTestCase
{
    public function testRegisterWithADisplayName(): void
    {
        $response = self::createClient()->request('POST', '/api/account', ['json' => ['email' => 'zoe@ludex.fr', 'plainPassword' => 'mot-de-passe', 'displayName' => 'Zoé']]);

        self::assertResponseStatusCodeSame(201);
        self::assertSame(['zoe@ludex.fr', 'Zoé', false], [$response->toArray()['email'], $response->toArray()['displayName'], $response->toArray()['demo']]);
    }

    public function testAShortPasswordIsRefused(): void
    {
        self::createClient()->request('POST', '/api/account', ['json' => ['email' => 'zoe@ludex.fr', 'plainPassword' => 'court']]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testChangingTheEmailGivesANewToken(): void
    {
        $bob = $this->bob();
        $response = $bob->request('PATCH', '/api/account', [
            'json' => ['email' => 'robert@ludex.fr', 'displayName' => 'Robert'],
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
        ]);

        self::assertResponseIsSuccessful();
        $token = $response->toArray()['token'];
        self::assertSame('Robert', $response->toArray()['displayName']);

        $client = self::createClient();
        $client->request('GET', '/api/account', ['headers' => ['Authorization' => "Bearer $token"]]);
        self::assertJsonContains(['email' => 'robert@ludex.fr']);
    }

    public function testChangingThePasswordNeedsTheCurrentOne(): void
    {
        $bob = $this->bob();
        $bob->request('POST', '/api/account/password', ['json' => ['currentPassword' => 'mauvais', 'newPassword' => 'nouveau-mot-de-passe']]);
        self::assertResponseStatusCodeSame(422);

        $bob->request('POST', '/api/account/password', ['json' => ['currentPassword' => 'bob-password', 'newPassword' => 'nouveau-mot-de-passe']]);
        self::assertResponseStatusCodeSame(204);

        $this->as('bob@ludex.fr', 'nouveau-mot-de-passe');
        self::assertResponseIsSuccessful();
    }

    public function testDeletingTheAccountRemovesItsData(): void
    {
        $bob = $this->bob();
        $bob->request('POST', '/api/collection_items', ['json' => [
            'game' => $this->iri(Game::class, ['title' => 'Celeste']),
            'platform' => $this->iri(Platform::class, ['slug' => 'pc']),
        ]]);

        $bob->request('POST', '/api/account/delete', ['json' => ['password' => 'mauvais']]);
        self::assertResponseStatusCodeSame(422);

        $bob->request('POST', '/api/account/delete', ['json' => ['password' => 'bob-password']]);
        self::assertResponseStatusCodeSame(204);

        self::assertNull($this->em->getRepository(User::class)->findOneBy(['email' => 'bob@ludex.fr']));
        self::assertSame(18, $this->em->getRepository(CollectionItem::class)->count([]), 'il ne reste que la collection de démo');
        self::assertSame(1, $this->em->getRepository(Game::class)->count(['title' => 'Celeste']), 'le catalogue ne bouge pas');
    }

    public function testTheDemoAccountItselfCannotBeChanged(): void
    {
        $demo = $this->demo();
        $demo->request('PATCH', '/api/account', ['json' => ['displayName' => 'Pirate'], 'headers' => ['Content-Type' => 'application/merge-patch+json']]);
        self::assertResponseStatusCodeSame(403);
        $demo->request('POST', '/api/account/password', ['json' => ['currentPassword' => DemoAccount::PASSWORD, 'newPassword' => 'je-change-tout']]);
        self::assertResponseStatusCodeSame(403);
        $demo->request('POST', '/api/account/delete', ['json' => ['password' => DemoAccount::PASSWORD]]);
        self::assertResponseStatusCodeSame(403);
        $demo->request('POST', '/api/account/steam/link');
        self::assertResponseStatusCodeSame(403);

        $demo->request('GET', '/api/account');
        self::assertJsonContains(['displayName' => DemoAccount::NAME, 'demo' => true]);
    }

    public function testTheDemoCollectionComesBackAfterTheNightlyReset(): void
    {
        $demo = $this->demo();
        foreach ($demo->request('GET', '/api/collection_items?itemsPerPage=5')->toArray()['member'] as $item) {
            $demo->request('DELETE', $item['@id']);
        }
        self::assertSame(13, $demo->request('GET', '/api/collection_items')->toArray()['totalItems']);

        self::getContainer()->get(DemoAccount::class)->reset();

        self::assertSame(18, $this->demo()->request('GET', '/api/collection_items')->toArray()['totalItems']);
    }
}
