<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Daily\DailyPuzzles;
use App\Dto\DailyGuess;
use App\Dto\DailyGuessResult;
use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * POST /api/daily/guess : compare l'essai au jeu mystère.
 * shortcut: le serveur croit le numéro d'essai envoyé (pas de partie en base) ; c'est un jeu, pas un concours.
 *
 * @implements ProcessorInterface<DailyGuess, DailyGuessResult>
 */
final readonly class DailyGuessProcessor implements ProcessorInterface
{
    public function __construct(private DailyPuzzles $puzzles, private EntityManagerInterface $em)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): DailyGuessResult
    {
        $guess = $this->em->find(Game::class, $data->game);
        if (!$guess || !\in_array($guess, $this->puzzles->candidates(), true)) {
            throw new UnprocessableEntityHttpException("Ce jeu ne fait pas partie des jeux proposables (voir « choices » dans GET /api/daily).");
        }

        $answer = $this->puzzles->answer(DailyPuzzles::today());
        $comparison = $this->puzzles->compare($guess, $answer);
        $over = $comparison['correct'] || $data->attempt >= DailyPuzzles::MAX_ATTEMPTS;

        return new DailyGuessResult(
            correct: $comparison['correct'],
            attempt: $data->attempt,
            title: $guess->getTitle(),
            releaseYear: $guess->getReleaseYear(),
            year: $comparison['year'],
            yearGap: $comparison['yearGap'],
            genres: $comparison['genres'],
            platforms: $comparison['platforms'],
            hint: !$over && $data->attempt >= 3 ? DailyPuzzles::hint($answer) : null,
            answer: $over ? ['title' => $answer->getTitle(), 'releaseYear' => $answer->getReleaseYear(), 'coverUrl' => $answer->getCoverUrl()] : null,
        );
    }
}
