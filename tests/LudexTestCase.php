<?php

namespace App\Tests;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Test\Client;
use App\DataFixtures\AppFixtures;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Base des tests : une base SQLite recréée avant chaque test, avec le catalogue et le compte de démo
 * des fixtures, plus un second joueur, Bob.
 */
abstract class LudexTestCase extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    protected EntityManagerInterface $em;

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

        // Les limites de débit (connexion, inscription, synchro Steam) repartent de zéro à chaque test
        self::getContainer()->get('cache.rate_limiter')->clear();
    }

    protected function as(string $email, string $password): Client
    {
        $client = self::createClient();
        $token = $client->request('POST', '/api/auth', ['json' => ['email' => $email, 'password' => $password]])->toArray()['token'];
        $client->setDefaultOptions(['auth_bearer' => $token, 'headers' => ['Accept' => 'application/ld+json']]);

        return $client;
    }

    protected function demo(): Client
    {
        return $this->as(AppFixtures::DEMO_EMAIL, AppFixtures::DEMO_PASSWORD);
    }

    protected function bob(): Client
    {
        return $this->as('bob@ludex.fr', 'bob-password');
    }

    protected function iri(string $class, array $criteria): string
    {
        $entity = $this->em->getRepository($class)->findOneBy($criteria);

        return self::findIriBy($class, ['id' => $entity->getId()]);
    }
}
