<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use App\Entity\OwnedInterface;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Pose le propriétaire (l'utilisateur connecté) avant l'enregistrement.
 *
 * API Platform valide avant les processeurs : à ce moment, le propriétaire est encore vide et
 * UniqueEntity(owner, game) ne voit pas les doublons. On revalide donc une fois le propriétaire posé,
 * pour renvoyer une erreur 422 plutôt qu'une violation de contrainte en base.
 *
 * @implements ProcessorInterface<OwnedInterface, OwnedInterface>
 */
final readonly class OwnerProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persist,
        private Security $security,
        private ValidatorInterface $validator,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();
        if ($data instanceof OwnedInterface && $user instanceof User && null === $data->getOwner()) {
            $data->setOwner($user);
            $this->validator->validate($data, ['groups' => $operation->getValidationContext()['groups'] ?? null]);
        }

        return $this->persist->process($data, $operation, $uriVariables, $context);
    }
}
