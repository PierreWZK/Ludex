<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/** Le jeu mystère d'un jour : choisi au premier appel de la journée, puis le même pour tout le monde */
#[ORM\Entity]
class DailyPuzzle
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    /** La date à Paris, « 2026-10-24 » */
    #[ORM\Column(length: 10, unique: true)]
    private string $day;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Game $game;

    public function __construct(string $day, Game $game)
    {
        $this->day = $day;
        $this->game = $game;
    }

    public function getDay(): string { return $this->day; }
    public function getGame(): Game { return $this->game; }
}
