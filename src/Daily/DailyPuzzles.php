<?php

namespace App\Daily;

use App\Entity\DailyPuzzle;
use App\Entity\Game;
use App\Entity\Genre;
use App\Entity\Platform;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Le jeu mystère du jour : un jeu du catalogue à retrouver en 6 essais. Chaque essai est comparé
 * à la réponse (année, genres, plateformes), comme un jeu de mots où les lettres seraient des indices.
 */
final class DailyPuzzles
{
    public const MAX_ATTEMPTS = 6;
    /** Le premier jeu du jour : il porte le numéro 1 */
    public const FIRST_DAY = '2026-10-23';
    /** Un même jeu ne revient pas avant ce nombre de jours */
    private const NO_REPEAT_DAYS = 30;

    public function __construct(private EntityManagerInterface $em, private ManagerRegistry $doctrine)
    {
    }

    public static function today(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))->format('Y-m-d');
    }

    public static function number(string $day): int
    {
        return (int) (new \DateTimeImmutable(self::FIRST_DAY))->diff(new \DateTimeImmutable($day))->format('%r%a') + 1;
    }

    /**
     * Les jeux qu'on peut proposer, et parmi lesquels se choisit la réponse : ceux qui ont une année et un genre.
     *
     * @return list<Game>
     */
    public function candidates(): array
    {
        return $this->em->createQueryBuilder()
            ->select('DISTINCT g')->from(Game::class, 'g')->join('g.genres', 'genre')
            ->where('g.releaseYear IS NOT NULL')
            ->orderBy('g.title')
            ->getQuery()->getResult();
    }

    /** La réponse du jour, tirée une fois puis gardée en base */
    public function answer(string $day): Game
    {
        $puzzle = $this->em->getRepository(DailyPuzzle::class)->findOneBy(['day' => $day]);
        if ($puzzle) {
            return $puzzle->getGame();
        }

        $pool = $this->candidates();
        usort($pool, fn (Game $a, Game $b) => $a->getId() <=> $b->getId());
        $recent = array_map(fn (DailyPuzzle $p) => $p->getGame()->getId(), $this->em->createQueryBuilder()
            ->select('p')->from(DailyPuzzle::class, 'p')
            ->where('p.day >= :since')->setParameter('since', (new \DateTimeImmutable($day))->modify('-'.self::NO_REPEAT_DAYS.' days')->format('Y-m-d'))
            ->getQuery()->getResult());

        // Un tirage qui dépend de la date seule : le même résultat si deux visiteurs arrivent en même temps
        $index = crc32('ludex|'.$day) % \count($pool);
        for ($i = 0; $i < \count($pool) && \in_array($pool[$index]->getId(), $recent, true); ++$i) {
            $index = ($index + 1) % \count($pool);
        }
        $game = $pool[$index];

        try {
            $this->em->persist(new DailyPuzzle($day, $game));
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            // Un autre visiteur l'a enregistré au même instant : on relit le sien, identique
            $this->doctrine->resetManager();
            $game = $this->doctrine->getManager()->getRepository(DailyPuzzle::class)->findOneBy(['day' => $day])->getGame();
        }

        return $game;
    }

    /**
     * Compare un essai à la réponse.
     *
     * @return array{correct: bool, year: string, yearGap: int, genres: list<array{name: string, match: bool}>, platforms: list<array{name: string, match: bool}>}
     */
    public function compare(Game $guess, Game $answer): array
    {
        $gap = $answer->getReleaseYear() - $guess->getReleaseYear();
        $answerGenres = $answer->getGenres()->map(fn (Genre $g) => $g->getId())->toArray();
        $answerPlatforms = $answer->getPlatforms()->map(fn (Platform $p) => $p->getId())->toArray();

        return [
            'correct' => $guess->getId() === $answer->getId(),
            // plus_recent : la réponse est sortie après l'essai
            'year' => 0 === $gap ? 'egal' : ($gap > 0 ? 'plus_recent' : 'plus_ancien'),
            'yearGap' => abs($gap),
            'genres' => array_values($guess->getGenres()->map(fn (Genre $g) => ['name' => $g->getName(), 'match' => \in_array($g->getId(), $answerGenres, true)])->toArray()),
            'platforms' => array_values($guess->getPlatforms()->map(fn (Platform $p) => ['name' => $p->getName(), 'match' => \in_array($p->getId(), $answerPlatforms, true)])->toArray()),
        ];
    }

    /** L'indice offert à partir du troisième essai raté : l'initiale et le nombre de mots du titre */
    public static function hint(Game $answer): string
    {
        $words = \count(preg_split('/\s+/u', trim($answer->getTitle())));

        return sprintf('Le titre commence par « %s » et compte %d mot%s.', mb_strtoupper(mb_substr($answer->getTitle(), 0, 1)), $words, $words > 1 ? 's' : '');
    }
}
