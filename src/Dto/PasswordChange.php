<?php

namespace App\Dto;

use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints as Assert;

/** POST /api/account/password */
final class PasswordChange
{
    #[UserPassword(message: 'Mot de passe actuel incorrect.')]
    public string $currentPassword = '';

    #[Assert\NotBlank, Assert\Length(min: 8, max: 4096)]
    public string $newPassword = '';
}
