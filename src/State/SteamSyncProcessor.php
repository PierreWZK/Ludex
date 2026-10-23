<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\SteamAccount;
use App\Entity\User;
use App\Message\SyncSteamLibrary;
use App\Steam\SteamClient;
use App\Steam\SteamNotConfigured;
use App\Steam\SteamSync;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * POST /api/account/steam/sync : met la synchronisation en file d'attente, une fois par heure au plus.
 *
 * @implements ProcessorInterface<null, SteamAccount>
 */
final readonly class SteamSyncProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private SteamClient $steam,
        private EntityManagerInterface $em,
        private MessageBusInterface $bus,
        private RateLimiterFactoryInterface $steamSyncLimiter,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SteamAccount
    {
        $user = $this->security->getUser();
        \assert($user instanceof User);

        if (!$user->getSteamId()) {
            throw new ConflictHttpException("Aucun compte Steam lié : commencez par POST /api/account/steam/link.");
        }
        if (!$this->steam->isConfigured()) {
            throw new SteamNotConfigured();
        }
        $limit = $this->steamSyncLimiter->create((string) $user->getId())->consume();
        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException($limit->getRetryAfter()->getTimestamp() - time(), 'Une synchronisation par heure au plus : réessayez plus tard.');
        }

        $user->setSteamSyncStatus(SteamSync::PENDING);
        $this->em->flush();
        $this->bus->dispatch(new SyncSteamLibrary($user->getId()));
        $this->em->refresh($user);

        return SteamAccountProvider::of($user);
    }
}
