<?php

namespace App;

use App\Message\ResetDemoAccount;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/** Les tâches planifiées, exécutées par le worker Messenger du conteneur (transport scheduler_default) */
#[AsSchedule]
class Schedule implements ScheduleProviderInterface
{
    public function __construct(private CacheInterface $cache)
    {
    }

    public function getSchedule(): SymfonySchedule
    {
        // Ancrée sur un minuit de Paris : chaque jour à 00:00, heure d'été comme d'hiver
        $midnight = new \DateTimeImmutable('2026-01-01 00:00:00', new \DateTimeZone('Europe/Paris'));

        return (new SymfonySchedule())
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true)
            ->add(RecurringMessage::every('1 day', new ResetDemoAccount(), from: $midnight));
    }
}
