<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\State\SteamAccountProvider;
use App\State\SteamLinkProcessor;
use App\State\SteamSyncProcessor;
use App\State\SteamUnlinkProcessor;

/** Le compte Steam lié au joueur connecté, et les actions qui vont avec */
#[ApiResource(
    shortName: 'Steam',
    operations: [
        new Get(uriTemplate: '/steam', provider: SteamAccountProvider::class, description: 'État de la liaison et de la dernière synchronisation'),
        new Post(uriTemplate: '/steam/link', input: false, status: 200, processor: SteamLinkProcessor::class,
            description: "Renvoie l'adresse « Se connecter via Steam » : ouvrez-la dans un navigateur, Steam vous renvoie sur Ludex une fois le compte prouvé"),
        new Post(uriTemplate: '/steam/sync', input: false, status: 202, processor: SteamSyncProcessor::class,
            description: 'Lance la synchronisation en tâche de fond (une fois par heure au plus)'),
        new Delete(uriTemplate: '/steam', provider: SteamAccountProvider::class, processor: SteamUnlinkProcessor::class,
            description: 'Délie le compte Steam ; les jeux importés restent dans la collection'),
    ],
    security: "is_granted('ROLE_USER')",
)]
final class SteamAccount
{
    public function __construct(
        public bool $linked = false,
        public ?string $steamId = null,
        public ?string $persona = null,
        public ?string $avatar = null,
        public ?\DateTimeImmutable $syncedAt = null,
        /** ok, en_attente, profil_prive, details_prives ou erreur */
        public ?string $syncStatus = null,
        /** Ce qu'il faut faire, en clair, quand la synchronisation n'a pas abouti */
        public ?string $message = null,
        /** Seulement après POST /api/steam/link */
        public ?string $linkUrl = null,
    ) {
    }
}
