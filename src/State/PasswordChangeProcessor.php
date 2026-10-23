<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Demo\DemoGuard;
use App\Dto\PasswordChange;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * POST /api/account/password : le mot de passe actuel est vérifié par la validation (UserPassword).
 *
 * @implements ProcessorInterface<PasswordChange, null>
 */
final readonly class PasswordChangeProcessor implements ProcessorInterface
{
    public function __construct(private Security $security, private UserPasswordHasherInterface $hasher, private EntityManagerInterface $em)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $user = $this->security->getUser();
        \assert($user instanceof User);
        DemoGuard::deny($user, 'changer de mot de passe');

        $user->setPassword($this->hasher->hashPassword($user, $data->newPassword));
        $this->em->flush();

        return null;
    }
}
