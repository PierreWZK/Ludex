<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Inscription : hache le mot de passe, puis enregistre. Limitée par adresse IP contre les inscriptions en masse.
 *
 * @implements ProcessorInterface<User, User>
 */
final readonly class RegistrationProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persist,
        private UserPasswordHasherInterface $hasher,
        private RequestStack $requests,
        private RateLimiterFactoryInterface $registrationLimiter,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        $limit = $this->registrationLimiter->create($this->requests->getCurrentRequest()?->getClientIp() ?? 'cli')->consume();
        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException($limit->getRetryAfter()->getTimestamp() - time(), 'Trop d\'inscriptions depuis cette adresse, réessayez plus tard.');
        }

        $data->setPassword($this->hasher->hashPassword($data, (string) $data->getPlainPassword()));
        $data->setPlainPassword(null);

        return $this->persist->process($data, $operation, $uriVariables, $context);
    }
}
