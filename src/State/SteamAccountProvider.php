<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\SteamAccount;
use App\Entity\User;
use App\Steam\SteamSync;
use Symfony\Bundle\SecurityBundle\Security;

/** @implements ProviderInterface<SteamAccount> */
final readonly class SteamAccountProvider implements ProviderInterface
{
    private const MESSAGES = [
        SteamSync::PENDING => 'Synchronisation en cours, revenez dans une minute.',
        SteamSync::PRIVATE_PROFILE => 'Votre profil Steam est privé : Steam > Profil > Modifier le profil > Confidentialité, passez « Mon profil » en Public.',
        SteamSync::PRIVATE_GAMES => 'Vos jeux sont masqués : dans les réglages de confidentialité Steam, passez « Détails des jeux » en Public (et décochez « Toujours garder mon temps de jeu privé »).',
        SteamSync::ERROR => 'Steam n\'a pas répondu correctement. Réessayez plus tard.',
    ];

    public function __construct(private Security $security)
    {
    }

    public static function of(User $user, ?string $linkUrl = null): SteamAccount
    {
        return new SteamAccount(
            linked: null !== $user->getSteamId(),
            steamId: $user->getSteamId(),
            persona: $user->getSteamPersona(),
            avatar: $user->getSteamAvatar(),
            syncedAt: $user->getSteamSyncedAt(),
            syncStatus: $user->getSteamSyncStatus(),
            message: self::MESSAGES[$user->getSteamSyncStatus()] ?? null,
            linkUrl: $linkUrl,
        );
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SteamAccount
    {
        $user = $this->security->getUser();
        \assert($user instanceof User);

        return self::of($user);
    }
}
