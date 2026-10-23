<?php

namespace App\Dto;

use App\Daily\DailyPuzzles;
use Symfony\Component\Validator\Constraints as Assert;

/** POST /api/daily/guess */
final class DailyGuess
{
    /** L'identifiant du jeu proposé (voir « choices » dans GET /api/daily) */
    #[Assert\Positive]
    public int $game = 0;

    /** Le numéro de cet essai, de 1 à 6 : la réponse est révélée au sixième */
    #[Assert\Range(min: 1, max: DailyPuzzles::MAX_ATTEMPTS)]
    public int $attempt = 1;
}
