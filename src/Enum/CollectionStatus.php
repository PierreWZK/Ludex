<?php

namespace App\Enum;

/** Où en est le joueur avec un jeu de sa collection */
enum CollectionStatus: string
{
    case Backlog = 'backlog';     // à faire
    case Playing = 'playing';     // en cours
    case Finished = 'finished';   // fini
    case Abandoned = 'abandoned'; // abandonné

    public function label(): string
    {
        return match ($this) {
            self::Backlog => 'À faire',
            self::Playing => 'En cours',
            self::Finished => 'Fini',
            self::Abandoned => 'Abandonné',
        };
    }
}
