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

    /** SteamID64 prouvé par OpenID : un compte Steam ne se lie qu'à un seul compte Ludex */
    #[ORM\Column(length: 20, unique: true, nullable: true)]
    private ?string $steamId = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $steamPersona = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $steamAvatar = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $steamSyncedAt = null;

    /** ok, en_attente, profil_prive, details_prives ou erreur */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $steamSyncStatus = null;

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
    public function getSteamId(): ?string { return $this->steamId; }
    public function setSteamId(?string $steamId): static { $this->steamId = $steamId; return $this; }
    public function getSteamPersona(): ?string { return $this->steamPersona; }
    public function setSteamPersona(?string $steamPersona): static { $this->steamPersona = $steamPersona; return $this; }
    public function getSteamAvatar(): ?string { return $this->steamAvatar; }
    public function setSteamAvatar(?string $steamAvatar): static { $this->steamAvatar = $steamAvatar; return $this; }
    public function getSteamSyncedAt(): ?\DateTimeImmutable { return $this->steamSyncedAt; }
    public function setSteamSyncedAt(?\DateTimeImmutable $steamSyncedAt): static { $this->steamSyncedAt = $steamSyncedAt; return $this; }
    public function getSteamSyncStatus(): ?string { return $this->steamSyncStatus; }
    public function setSteamSyncStatus(?string $steamSyncStatus): static { $this->steamSyncStatus = $steamSyncStatus; return $this; }
}
