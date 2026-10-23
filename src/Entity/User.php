<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Demo\DemoAccount;
use App\Dto\AccountDeletion;
use App\Dto\PasswordChange;
use App\State\AccountDeletionProcessor;
use App\State\AccountUpdateProcessor;
use App\State\MeProvider;
use App\State\PasswordChangeProcessor;
use App\State\RegistrationProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Un joueur : son compte, sa collection et sa liste d'envies.
 * Tout ce qui touche au compte passe par /api/account, depuis le site comme depuis l'API.
 */
#[ORM\Entity]
#[ORM\Table(name: '`user`')]
#[UniqueEntity('email', message: 'Un compte existe déjà avec cet e-mail.')]
#[ApiResource(
    shortName: 'Account',
    operations: [
        new Post(uriTemplate: '/account', processor: RegistrationProcessor::class, description: 'Créer un compte',
            denormalizationContext: ['groups' => ['user:register']], validationContext: ['groups' => ['Default', 'register']]),
        new Get(uriTemplate: '/account', provider: MeProvider::class, security: "is_granted('ROLE_USER')", description: 'Mon compte'),
        new Patch(uriTemplate: '/account', read: true, provider: MeProvider::class, processor: AccountUpdateProcessor::class, security: "is_granted('ROLE_USER')",
            denormalizationContext: ['groups' => ['account:update']], description: "Changer de pseudo ou d'e-mail (un nouveau jeton est renvoyé si l'e-mail change)"),
        new Post(uriTemplate: '/account/password', input: PasswordChange::class, output: false, status: 204, processor: PasswordChangeProcessor::class,
            security: "is_granted('ROLE_USER')", description: 'Changer de mot de passe (le mot de passe actuel est demandé)'),
        new Post(uriTemplate: '/account/delete', input: AccountDeletion::class, output: false, status: 204, processor: AccountDeletionProcessor::class,
            security: "is_granted('ROLE_USER')", description: 'Supprimer son compte et toutes ses données (le mot de passe est demandé)'),
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
    #[Groups(['user:read', 'user:register', 'account:update'])]
    private string $email = '';

    /** Le nom affiché sur le site ; l'e-mail n'est jamais montré aux autres */
    #[ORM\Column(length: 40, nullable: true)]
    #[Assert\Length(min: 2, max: 40)]
    #[Groups(['user:read', 'user:register', 'account:update'])]
    private ?string $displayName = null;

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private string $password = '';

    /** Le mot de passe en clair, le temps de l'inscription : jamais stocké ni renvoyé */
    #[Assert\NotBlank(groups: ['register']), Assert\Length(min: 8, max: 4096, groups: ['register'])]
    #[Groups(['user:register'])]
    private ?string $plainPassword = null;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Groups(['user:read'])]
    private \DateTimeImmutable $createdAt;

    /** Renvoyé une seule fois, après un changement d'e-mail : l'ancien jeton portait l'ancien e-mail */
    #[ApiProperty(readable: true, writable: false)]
    #[Groups(['user:read'])]
    private ?string $token = null;

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

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getDisplayName(): ?string { return $this->displayName; }
    public function setDisplayName(?string $displayName): static { $this->displayName = null === $displayName ? null : (trim($displayName) ?: null); return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getToken(): ?string { return $this->token; }
    public function setToken(?string $token): static { $this->token = $token; return $this; }

    /** Le compte de démo, partagé par tous les visiteurs : réinitialisé chaque nuit, son compte ne se modifie pas */
    #[Groups(['user:read'])]
    public function isDemo(): bool { return DemoAccount::EMAIL === $this->email; }

    /** Le compte Steam lié, résumé pour le site */
    #[Groups(['user:read'])]
    public function getSteam(): ?array
    {
        return $this->steamId ? ['steamId' => $this->steamId, 'persona' => $this->steamPersona, 'avatar' => $this->steamAvatar] : null;
    }

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
