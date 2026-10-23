<?php

namespace App\Security;

use App\Entity\OwnedInterface;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * is_granted('OWNER', object) : seul le propriétaire lit, modifie ou supprime un élément de collection ou d'envies.
 *
 * @extends Voter<string, OwnedInterface>
 */
final class OwnerVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return 'OWNER' === $attribute && $subject instanceof OwnedInterface;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        return $user instanceof User && $subject->getOwner()?->getId() === $user->getId();
    }
}
