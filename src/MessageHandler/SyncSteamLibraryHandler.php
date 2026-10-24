<?php

namespace App\MessageHandler;

use App\Entity\User;
use App\Message\SyncSteamLibrary;
use App\Steam\SteamSync;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SyncSteamLibraryHandler
{
    public function __construct(private EntityManagerInterface $em, private SteamSync $sync, private LoggerInterface $logger)
    {
    }

    public function __invoke(SyncSteamLibrary $message): void
    {
        $user = $this->em->find(User::class, $message->userId);
        if (!$user?->getSteamId()) {
            return;
        }

        try {
            $this->sync->sync($user);
        } catch (\Throwable $e) {
            // Steam indisponible ou réponse inattendue : le joueur voit « erreur » et peut relancer plus tard
            // {error} dans le message : sans Monolog, le logger par défaut n'écrit pas l'exception du contexte
            $this->logger->error('Synchronisation Steam impossible : {error}', ['user' => $message->userId, 'error' => $e::class.' : '.$e->getMessage(), 'exception' => $e]);
            if ($this->em->isOpen()) {
                $user->setSteamSyncStatus(SteamSync::ERROR)->setSteamSyncedAt(new \DateTimeImmutable());
                $this->em->flush();
            }
        }
    }
}
