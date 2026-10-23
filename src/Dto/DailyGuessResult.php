<?php

namespace App\Dto;

/** La réponse à un essai du jeu du jour */
final class DailyGuessResult
{
    /**
     * @param list<array{name: string, match: bool}> $genres    les genres de l'essai, et s'ils sont aussi ceux du jeu mystère
     * @param list<array{name: string, match: bool}> $platforms les plateformes de l'essai, et si le jeu mystère y est aussi
     * @param array{title: string, releaseYear: int, coverUrl: ?string}|null $answer révélée une fois trouvée ou au dernier essai
     */
    public function __construct(
        public bool $correct,
        public int $attempt,
        public string $title,
        public int $releaseYear,
        /** egal, plus_recent (le jeu mystère est sorti après) ou plus_ancien */
        public string $year,
        public int $yearGap,
        public array $genres,
        public array $platforms,
        public ?string $hint = null,
        public ?array $answer = null,
    ) {
    }
}
