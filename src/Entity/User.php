<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\State\MeProvider;
use App\State\RegistrationProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/** Un joueur : un e-mail, un mot de passe, sa collection et sa liste d'envies */
#[ORM\Entity]
#[ORM\Table(name: '`user`')]
#[UniqueEntity('email', message: 'Un compte existe déjà avec cet e-mail.')]
#[ApiResource(
    operations: [
        new Post(uriTemplate: '/register', processor: RegistrationProcessor::class, denormalizationContext: ['groups' => ['user:register']]),
        new Get(uriTemplate: '/me', provider: MeProvider::class, security: "is_granted('ROLE_USER')"),
    ],
    normalizationContext: ['groups' => ['user:read']],
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank, Assert\Email, Assert\Length(max: 180)]
    #[Groups(['user:read', 'user:register'])]
    private string $email = '';

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private string $password = '';

    /** Le mot de passe en clair, le temps de l'inscription : jamais stocké ni renvoyé */
    #[Assert\NotBlank, Assert\Length(min: 8, max: 4096)]
    #[Groups(['user:register'])]
    private ?string $plainPassword = null;

    public function getId(): ?int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): static { $this->email = mb_strtolower(trim($email)); return $this; }
    public function getUserIdentifier(): string { return $this->email; }

    /** @return list<string> */
    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, 'ROLE_USER']));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }
    public function getPlainPassword(): ?string { return $this->plainPassword; }
    public function setPlainPassword(?string $plainPassword): static { $this->plainPassword = $plainPassword; return $this; }
}
