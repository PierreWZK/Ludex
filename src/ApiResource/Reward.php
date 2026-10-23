<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\State\RewardProvider;

/** Les récompenses : toutes, avec celles du joueur connecté débloquées et sa progression vers les autres */
#[ApiResource(
    operations: [new GetCollection(uriTemplate: '/account/rewards', paginationEnabled: false, provider: RewardProvider::class)],
    security: "is_granted('ROLE_USER')",
)]
final class Reward
{
    public function __construct(
        public string $code,
        public string $name,
        public string $description,
        /** badge, cadre ou titre */
        public string $cosmetic,
        public bool $unlocked,
        public ?\DateTimeImmutable $unlockedAt,
        public int $progress,
        public int $threshold,
    ) {
    }
}
