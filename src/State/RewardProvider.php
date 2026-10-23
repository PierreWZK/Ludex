<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Reward;
use App\Entity\User;
use App\Reward\RewardEngine;
use App\Reward\Rewards;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Évalue les récompenses à chaque lecture : une collection remplie à la main débloque aussi les siennes.
 *
 * @implements ProviderInterface<Reward>
 */
final readonly class RewardProvider implements ProviderInterface
{
    public function __construct(private RewardEngine $engine, private Security $security)
    {
    }

    /** @return list<Reward> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();
        \assert($user instanceof User);

        $unlocked = $this->engine->evaluate($user);
        $metrics = $this->engine->metrics($user);

        return array_map(fn (array $r) => new Reward(
            code: $r['code'],
            name: $r['name'],
            description: $r['description'],
            cosmetic: $r['cosmetic'],
            unlocked: isset($unlocked[$r['code']]),
            unlockedAt: ($unlocked[$r['code']] ?? null)?->getUnlockedAt(),
            progress: min($metrics[$r['metric']], $r['threshold']),
            threshold: $r['threshold'],
        ), Rewards::ALL);
    }
}
