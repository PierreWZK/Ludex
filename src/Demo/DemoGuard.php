<?php

namespace App\Demo;

use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** Le compte de démo est partagé : sa collection se modifie (elle revient chaque nuit), son compte non */
final class DemoGuard
{
    public static function deny(User $user, string $action): void
    {
        if ($user->isDemo()) {
            throw new AccessDeniedHttpException("Le compte de démo ne peut pas $action : créez votre propre compte pour essayer.");
        }
    }
}
