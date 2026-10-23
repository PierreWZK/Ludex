<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\OwnedInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Restreint chaque requête sur une ressource possédée à l'utilisateur connecté.
 * Pour un élément d'un autre joueur, la requête ne trouve rien : 404, sans révéler qu'il existe.
 */
final readonly class OwnerExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(private Security $security)
    {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $this->restrict($queryBuilder, $queryNameGenerator, $resourceClass);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    {
        $this->restrict($queryBuilder, $queryNameGenerator, $resourceClass);
    }

    private function restrict(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass): void
    {
        if (!is_a($resourceClass, OwnedInterface::class, true)) {
            return;
        }

        // Sans utilisateur, la sécurité de la ressource a déjà refusé l'accès ; on ne renvoie rien par prudence
        $parameter = $queryNameGenerator->generateParameterName('owner');
        $queryBuilder
            ->andWhere(sprintf('%s.owner = :%s', $queryBuilder->getRootAliases()[0], $parameter))
            ->setParameter($parameter, $this->security->getUser());
    }
}
