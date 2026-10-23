<?php

namespace App\Command;

use App\Demo\DemoAccount;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/** La même remise à zéro que celle de minuit, à la main */
#[AsCommand('app:demo:reset', 'Remet le compte de démo à son état d\'origine')]
final class ResetDemoCommand
{
    public function __construct(private readonly DemoAccount $demo)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $this->demo->reset();
        $io->success('Compte de démo réinitialisé.');

        return Command::SUCCESS;
    }
}
