<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\QueryParameter;
use App\Entity\CollectionItem;
use App\State\AchievementProgressProvider;

/**
 * Les succès d'un jeu de la collection : obtenus ou à faire.
 * ?state=todo : ceux qui restent, du plus courant au plus rare ; ?state=done : ceux obtenus, du plus récent au plus ancien.
 */
#[ApiResource(
    shortName: 'Achievement',
    operations: [
        new GetCollection(
            uriTemplate: '/collection_items/{id}/achievements',
            uriVariables: ['id' => new Link(fromClass: CollectionItem::class)],
            paginationEnabled: false,
            provider: AchievementProgressProvider::class,
            parameters: ['state' => new QueryParameter(schema: ['type' => 'string', 'enum' => ['todo', 'done']], description: 'todo (à faire) ou done (obtenus)')],
        ),
    ],
    security: "is_granted('ROLE_USER')",
)]
final class AchievementProgress
{
    public function __construct(
        public string $apiName,
        public string $name,
        public ?string $description,
        public ?string $icon,
        public bool $hidden,
        /** Part des joueurs Steam qui l'ont obtenu, en % */
        public ?float $rarity,
        public bool $unlocked,
        public ?\DateTimeImmutable $unlockedAt,
    ) {
    }
}
