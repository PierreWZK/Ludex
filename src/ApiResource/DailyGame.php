<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Dto\DailyGuess;
use App\Dto\DailyGuessResult;
use App\State\DailyGameProvider;
use App\State\DailyGuessProcessor;

/**
 * Le jeu du jour : retrouver le jeu mystère en 6 essais, le même pour tout le monde, nouveau à minuit (Paris).
 * Ouvert à tous, sans compte.
 */
#[ApiResource(
    shortName: 'Daily',
    operations: [
        new Get(uriTemplate: '/daily', provider: DailyGameProvider::class, description: 'Le jeu du jour : numéro, essais et jeux proposables'),
        new Post(uriTemplate: '/daily/guess', input: DailyGuess::class, output: DailyGuessResult::class, status: 200, processor: DailyGuessProcessor::class,
            description: "Proposer un jeu : la réponse compare l'année, les genres et les plateformes avec le jeu mystère"),
    ],
)]
final class DailyGame
{
    /** @param list<array{id: int, title: string}> $choices */
    public function __construct(
        public string $date,
        public int $number,
        public int $maxAttempts,
        public array $choices,
    ) {
    }
}
