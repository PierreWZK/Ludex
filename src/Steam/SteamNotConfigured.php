<?php

namespace App\Steam;

use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/** STEAM_API_KEY n'est pas posée : la synchronisation est impossible */
final class SteamNotConfigured extends ServiceUnavailableHttpException
{
    public function __construct()
    {
        parent::__construct(null, "La connexion à Steam n'est pas configurée sur ce serveur (STEAM_API_KEY manquante).");
    }
}
