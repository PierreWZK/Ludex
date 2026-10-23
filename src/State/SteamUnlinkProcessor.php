<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * DELETE /api/account/steam : délie le compte. La collection importée et les succès restent.
 *
 * @implements ProcessorInterface<mixed, null>
 */
final readonly class SteamUnlinkProcessor implements ProcessorInterface
{
    public function __construct(private Security $security, private EntityManagerInterface $em)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $user = $this->security->getUser();
        \assert($user instanceof User);

        $user->setSteamId(null)->setSteamPersona(null)->setSteamAvatar(null)->setSteamSyncStatus(null)->setSteamSyncedAt(null);
        $this->em->flush();

        return null;
    }
}
