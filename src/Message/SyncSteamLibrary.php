<?php

namespace App\Message;

/** Demande de synchronisation Steam d'un joueur, traitée en tâche de fond */
final readonly class SyncSteamLibrary
{
    public function __construct(public int $userId)
    {
    }
}
