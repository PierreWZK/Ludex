<?php

namespace App\Dto;

use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

/** POST /api/account/delete : le mot de passe confirme la suppression */
final class AccountDeletion
{
    #[UserPassword(message: 'Mot de passe incorrect : le compte n\'est pas supprimé.')]
    public string $password = '';
}
