<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Demo\DemoGuard;
use App\Dto\AccountDeletion;
use App\Entity\CollectionItem;
use App\Entity\PlayerAchievement;
use App\Entity\User;
use App\Entity\UserReward;
use App\Entity\WishlistItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * POST /api/account/delete : supprime le compte avec sa collection, ses envies, ses succès et ses récompenses.
 * Les jeux importés restent au catalogue commun.
 *
 * @implements ProcessorInterface<AccountDeletion, null>
 */
final readonly class AccountDeletionProcessor implements ProcessorInterface
{
    public function __construct(private Security $security, private EntityManagerInterface $em)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $user = $this->security->getUser();
        \assert($user instanceof User);
        DemoGuard::deny($user, 'être supprimé');

        // Données du joueur d'abord (sans compter sur les cascades de la base, que SQLite ignore), puis le compte
        $this->em->wrapInTransaction(function () use ($user): void {
            foreach ([PlayerAchievement::class, UserReward::class, WishlistItem::class, CollectionItem::class] as $class) {
                $this->em->createQuery("DELETE FROM $class x WHERE x.owner = :user")->setParameter('user', $user)->execute();
            }
            $this->em->createQuery('DELETE FROM '.User::class.' u WHERE u.id = :id')->setParameter('id', $user->getId())->execute();
        });

        return null;
    }
}
