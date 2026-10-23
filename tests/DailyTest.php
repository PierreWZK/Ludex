<?php

namespace App\Tests;

use App\Daily\DailyPuzzles;
use App\Entity\DailyPuzzle;
use App\Entity\Game;

/** Le jeu du jour : même jeu mystère pour tout le monde, essais comparés, indice, réponse au sixième essai */
final class DailyTest extends LudexTestCase
{
    private function answer(): Game
    {
        self::createClient()->request('GET', '/api/daily');

        return $this->em->getRepository(DailyPuzzle::class)->findOneBy(['day' => DailyPuzzles::today()])->getGame();
    }

    private function guess(Game $game, int $attempt): array
    {
        return self::createClient()->request('POST', '/api/daily/guess', ['json' => ['game' => $game->getId(), 'attempt' => $attempt]])->toArray(false);
    }

    /** Un jeu qui n'est pas la réponse et sort une autre année */
    private function wrong(Game $answer): Game
    {
        foreach ($this->em->getRepository(Game::class)->findAll() as $game) {
            if ($game->getId() !== $answer->getId() && $game->getReleaseYear() !== $answer->getReleaseYear()) {
                return $game;
            }
        }
        self::fail('aucun autre jeu');
    }

    public function testTheDailyGameIsOpenToEveryoneAndStableAllDay(): void
    {
        $daily = self::createClient()->request('GET', '/api/daily')->toArray();

        self::assertResponseIsSuccessful();
        self::assertSame([DailyPuzzles::today(), 6, 54], [$daily['date'], $daily['maxAttempts'], \count($daily['choices'])]);
        self::createClient()->request('GET', '/api/daily');
        self::assertSame(1, $this->em->getRepository(DailyPuzzle::class)->count([]), 'tiré une seule fois');
    }

    public function testAWrongGuessIsComparedWithoutGivingTheAnswer(): void
    {
        $answer = $this->answer();
        $wrong = $this->wrong($answer);
        $result = $this->guess($wrong, 1);

        self::assertFalse($result['correct']);
        self::assertSame($answer->getReleaseYear() > $wrong->getReleaseYear() ? 'plus_recent' : 'plus_ancien', $result['year']);
        self::assertSame(abs($answer->getReleaseYear() - $wrong->getReleaseYear()), $result['yearGap']);
        self::assertCount($wrong->getPlatforms()->count(), $result['platforms']);
        self::assertArrayNotHasKey('answer', $result);
        self::assertArrayNotHasKey('hint', $result);
    }

    public function testAHintFromTheThirdAttemptAndTheAnswerAtTheSixth(): void
    {
        $answer = $this->answer();
        $wrong = $this->wrong($answer);

        self::assertStringStartsWith('Le titre commence par « '.mb_strtoupper(mb_substr($answer->getTitle(), 0, 1)), $this->guess($wrong, 3)['hint']);
        self::assertSame($answer->getTitle(), $this->guess($wrong, 6)['answer']['title']);
    }

    public function testTheRightGuessWins(): void
    {
        $answer = $this->answer();
        $result = $this->guess($answer, 2);

        self::assertTrue($result['correct']);
        self::assertSame('egal', $result['year']);
        self::assertSame($answer->getTitle(), $result['answer']['title']);
        self::assertNotContains(false, array_column($result['genres'], 'match'));
    }

    public function testAnUnknownGameIsRefused(): void
    {
        self::createClient()->request('POST', '/api/daily/guess', ['json' => ['game' => 999999, 'attempt' => 1]]);

        self::assertResponseStatusCodeSame(422);
    }
}
