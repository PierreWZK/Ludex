<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Demo\DemoGuard;
use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * PATCH /api/account : pseudo et e-mail. Le jeton porte l'e-mail : s'il change, un nouveau jeton est renvoyé.
 *
 * @implements ProcessorInterface<User, User>
 */
final readonly class AccountUpdateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persist,
        private JWTTokenManagerInterface $jwt,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        $before = $context['previous_data'] ?? null;
        DemoGuard::deny($data, 'être modifié');

        $user = $this->persist->process($data, $operation, $uriVariables, $context);
        if ($before instanceof User && $before->getEmail() !== $user->getEmail()) {
            $user->setToken($this->jwt->create($user));
        }

        return $user;
    }
}
