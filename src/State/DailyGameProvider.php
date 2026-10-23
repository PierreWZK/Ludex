<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\DailyGame;
use App\Daily\DailyPuzzles;
use App\Entity\Game;

/** @implements ProviderInterface<DailyGame> */
final readonly class DailyGameProvider implements ProviderInterface
{
    public function __construct(private DailyPuzzles $puzzles)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): DailyGame
    {
        $day = DailyPuzzles::today();
        // Tire (et enregistre) la réponse dès la première visite de la journée
        $this->puzzles->answer($day);

        return new DailyGame(
            date: $day,
            number: DailyPuzzles::number($day),
            maxAttempts: DailyPuzzles::MAX_ATTEMPTS,
            choices: array_map(fn (Game $g) => ['id' => $g->getId(), 'title' => $g->getTitle()], $this->puzzles->candidates()),
        );
    }
}
