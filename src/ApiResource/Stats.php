<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\StatsProvider;

/** GET /api/account/stats : les chiffres de la collection du joueur connecté */
#[ApiResource(
    operations: [new Get(uriTemplate: '/account/stats', provider: StatsProvider::class, description: 'Nombre de jeux, temps total, répartition par plateforme et par statut')],
    security: "is_granted('ROLE_USER')",
)]
final class Stats
{
    /**
     * @param array<string, int> $byStatus nombre de jeux par statut
     * @param list<array{platform: string, slug: string, games: int, playtimeMinutes: int}> $byPlatform
     */
    public function __construct(
        public int $games = 0,
        public int $playtimeMinutes = 0,
        public float $playtimeHours = 0.0,
        public ?float $averageRating = null,
        public int $wishlist = 0,
        public int $achievements = 0,
        public int $platinums = 0,
        public array $byStatus = [],
        public array $byPlatform = [],
    ) {
    }
}
