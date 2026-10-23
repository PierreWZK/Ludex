<?php

namespace App\MessageHandler;

use App\Demo\DemoAccount;
use App\Message\ResetDemoAccount;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ResetDemoAccountHandler
{
    public function __construct(private DemoAccount $demo)
    {
    }

    public function __invoke(ResetDemoAccount $message): void
    {
        $this->demo->reset();
    }
}
