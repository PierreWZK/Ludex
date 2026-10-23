<?php

namespace App\Tests;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Test\Client;
use App\DataFixtures\AppFixtures;
use App\Entity\CollectionItem;
use App\Entity\Game;
use App\Entity\Platform;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Le parcours d'un joueur de bout en bout, sur une base SQLite recréée avant chaque test :
 * le catalogue et le compte de démo des fixtures, plus un second joueur, Bob.
 */
final class ApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $tool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);

        self::getContainer()->get(AppFixtures::class)->load($this->em);
        $bob = (new User())->setEmail('bob@ludex.fr');
        $bob->setPassword(self::getContainer()->get('security.user_password_hasher')->hashPassword($bob, 'bob-password'));
        $this->em->persist($bob);
        $this->em->flush();
        $this->em->clear();
    }

    private function as(string $email, string $password): Client
    {
        $client = self::createClient();
        $token = $client->request('POST', '/api/auth', ['json' => ['email' => $email, 'password' => $password]])->toArray()['token'];
        $client->setDefaultOptions(['auth_bearer' => $token, 'headers' => ['Accept' => 'application/ld+json']]);

        return $client;
    }

    private function demo(): Client
    {
        return $this->as(AppFixtures::DEMO_EMAIL, AppFixtures::DEMO_PASSWORD);
    }

    private function iri(string $class, array $criteria): string
    {
        $entity = $this->em->getRepository($class)->findOneBy($criteria);

        return self::findIriBy($class, ['id' => $entity->getId()]);
    }

    private function demoItemIri(): string
    {
        $demo = $this->em->getRepository(User::class)->findOneBy(['email' => AppFixtures::DEMO_EMAIL]);

        return self::findIriBy(CollectionItem::class, ['owner' => $demo]);
    }

    public function testCatalogueIsPublic(): void
    {
        $response = self::createClient()->request('GET', '/api/games', ['headers' => ['Accept' => 'application/ld+json']]);

        self::assertResponseIsSuccessful();
        self::assertSame(54, $response->toArray()['totalItems']);
        self::assertCount(20, $response->toArray()['member'], 'pagination de 20');
    }

    public function testCollectionNeedsAToken(): void
    {
        self::createClient()->request('GET', '/api/collection_items');

        self::assertResponseStatusCodeSame(401);
    }

    public function testWrongPasswordIsRefused(): void
    {
        self::createClient()->request('POST', '/api/auth', ['json' => ['email' => AppFixtures::DEMO_EMAIL, 'password' => 'mauvais']]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testEachPlayerSeesOnlyTheirCollection(): void
    {
        self::assertSame(18, $this->demo()->request('GET', '/api/collection_items')->toArray()['totalItems']);
        self::assertSame(0, $this->as('bob@ludex.fr', 'bob-password')->request('GET', '/api/collection_items')->toArray()['totalItems']);
    }

    public function testAPlayerCannotReadOrChangeAnotherCollection(): void
    {
        $item = $this->demoItemIri();
        $bob = $this->as('bob@ludex.fr', 'bob-password');

        $bob->request('GET', $item);
        self::assertResponseStatusCodeSame(404);

        $bob->request('PATCH', $item, ['json' => ['rating' => 1], 'headers' => ['Content-Type' => 'application/merge-patch+json']]);
        self::assertResponseStatusCodeSame(404);

        $bob->request('DELETE', $item);
        self::assertResponseStatusCodeSame(404);

        $wish = self::findIriBy(\App\Entity\WishlistItem::class, ['priority' => 1]);
        $bob->request('GET', $wish);
        self::assertResponseStatusCodeSame(404);

        $this->demo()->request('GET', $item);
        self::assertResponseIsSuccessful();
    }

    public function testAddingAGameSetsTheOwner(): void
    {
        $bob = $this->as('bob@ludex.fr', 'bob-password');
        $bob->request('POST', '/api/collection_items', ['json' => [
            'game' => $this->iri(Game::class, ['title' => 'Celeste']),
            'platform' => $this->iri(Platform::class, ['slug' => 'switch']),
            'status' => 'playing',
            'playtimeMinutes' => 90,
            'rating' => 9,
        ]]);

        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains(['status' => 'playing', 'game' => ['title' => 'Celeste']]);
        self::assertSame(1, $bob->request('GET', '/api/collection_items')->toArray()['totalItems']);
        self::assertSame(18, $this->demo()->request('GET', '/api/collection_items')->toArray()['totalItems'], 'la démo n\'a rien reçu');
    }

    public function testFiltersCombine(): void
    {
        $demo = $this->demo();

        $finishedOnPs5 = $demo->request('GET', '/api/collection_items?status=finished&platform=ps5')->toArray();
        self::assertSame(2, $finishedOnPs5['totalItems'], 'Elden Ring et Astro Bot');

        $rpgOnPc = $demo->request('GET', '/api/collection_items?genre=rpg&platform=pc&order[playtimeMinutes]=desc')->toArray();
        self::assertSame(["Baldur's Gate 3", 'Cyberpunk 2077', 'Disco Elysium'], array_map(fn ($i) => $i['game']['title'], $rpgOnPc['member']));
    }

    public function testValidationErrorsUseProblemDetails(): void
    {
        $response = $this->demo()->request('POST', '/api/collection_items', ['json' => [
            'game' => $this->iri(Game::class, ['title' => 'Astro Bot']),
            'platform' => $this->iri(Platform::class, ['slug' => 'switch']),
            'rating' => 11,
            'playtimeMinutes' => -5,
        ]]);

        self::assertResponseStatusCodeSame(422);
        self::assertStringStartsWith('application/problem+json', $response->getHeaders(false)['content-type'][0]);
        $paths = array_column($response->toArray(false)['violations'], 'propertyPath');
        sort($paths);
        self::assertSame(['platform', 'playtimeMinutes', 'rating'], $paths);
        self::assertContains('Cette valeur doit être comprise entre 0 et 10.', array_column($response->toArray(false)['violations'], 'message'));
    }

    public function testTheSameGameCannotBeAddedTwiceOnOnePlatform(): void
    {
        $this->demo()->request('POST', '/api/collection_items', ['json' => [
            'game' => $this->iri(Game::class, ['title' => 'Elden Ring']),
            'platform' => $this->iri(Platform::class, ['slug' => 'ps5']),
        ]]);

        self::assertResponseStatusCodeSame(422);
        self::assertJsonContains(['violations' => [['propertyPath' => 'game']]]);
    }

    public function testStats(): void
    {
        $this->demo()->request('GET', '/api/stats');

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'games' => 18,
            'playtimeMinutes' => 757 * 60,
            'playtimeHours' => 757,
            'wishlist' => 5,
            'byStatus' => ['backlog' => 3, 'playing' => 5, 'finished' => 8, 'abandoned' => 2],
        ]);
    }

    public function testRegistrationThenLogin(): void
    {
        $client = self::createClient();
        $client->request('POST', '/api/register', ['json' => ['email' => 'Alice@Ludex.fr', 'plainPassword' => 'un-mot-de-passe']]);
        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains(['email' => 'alice@ludex.fr']);
        self::assertArrayNotHasKey('password', $client->getResponse()->toArray());

        $this->as('alice@ludex.fr', 'un-mot-de-passe')->request('GET', '/api/me');
        self::assertJsonContains(['email' => 'alice@ludex.fr']);
    }

    public function testOnlyAnAdminEditsTheCatalogue(): void
    {
        $this->demo()->request('POST', '/api/platforms', ['json' => ['name' => 'Dreamcast', 'slug' => 'dreamcast', 'maker' => 'Sega']]);

        self::assertResponseStatusCodeSame(403);
    }
}
