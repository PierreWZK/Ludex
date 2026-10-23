<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\SteamAccount;
use App\Entity\User;
use App\Steam\SteamOpenId;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * POST /api/steam/link : l'adresse de connexion Steam, avec un jeton d'état qui désigne le compte à lier.
 *
 * @implements ProcessorInterface<null, SteamAccount>
 */
final readonly class SteamLinkProcessor implements ProcessorInterface
{
    public function __construct(private Security $security, private SteamOpenId $openId, private UrlGeneratorInterface $urls)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SteamAccount
    {
        $user = $this->security->getUser();
        \assert($user instanceof User);

        $returnTo = $this->urls->generate('steam_callback', ['state' => $this->openId->createState($user->getId())], UrlGeneratorInterface::ABSOLUTE_URL);

        return SteamAccountProvider::of($user, $this->openId->loginUrl($returnTo));
    }
}
