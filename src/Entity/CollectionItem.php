<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\ExactFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\SortFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use App\Enum\CollectionStatus;
use App\State\OwnerProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/** Un jeu de la collection d'un joueur, sur une plateforme donnée */
#[ORM\Entity]
#[ORM\UniqueConstraint(fields: ['owner', 'game', 'platform'])]
#[UniqueEntity(fields: ['owner', 'game', 'platform'], message: 'Ce jeu est déjà dans votre collection sur cette plateforme.', errorPath: 'game')]
#[ApiResource(
    operations: [
        new GetCollection(parameters: [
            'status' => new QueryParameter(filter: new ExactFilter(), property: 'status', description: 'backlog, playing, finished ou abandoned'),
            'platform' => new QueryParameter(filter: new ExactFilter(), property: 'platform.slug', description: 'Slug de plateforme (ps5, switch…)'),
            'genre' => new QueryParameter(filter: new ExactFilter(), property: 'game.genres.slug', description: 'Slug de genre (rpg, aventure…)'),
            'title' => new QueryParameter(filter: new PartialSearchFilter(), property: 'game.title', description: 'Une partie du titre'),
            'exists[:property]' => new QueryParameter(filter: new ExistsFilter(), properties: ['platinumAt'], description: 'exists[platinumAt]=true : les platines'),
            'order[:property]' => new QueryParameter(filter: new SortFilter(), properties: ['addedAt', 'playtimeMinutes', 'rating', 'platinumAt']),
        ]),
        new Get(security: "is_granted('OWNER', object)"),
        new Post(processor: OwnerProcessor::class),
        new Patch(security: "is_granted('OWNER', object)", processor: OwnerProcessor::class),
        new Delete(security: "is_granted('OWNER', object)"),
    ],
    normalizationContext: ['groups' => ['item:read']],
    denormalizationContext: ['groups' => ['item:write']],
    order: ['addedAt' => 'DESC', 'id' => 'DESC'],
    security: "is_granted('ROLE_USER')",
)]
class CollectionItem implements OwnedInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups(['item:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $owner = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['item:read', 'item:write'])]
    private ?Game $game = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['item:read', 'item:write'])]
    private ?Platform $platform = null;

    #[ORM\Column(enumType: CollectionStatus::class)]
    #[Groups(['item:read', 'item:write'])]
    private CollectionStatus $status = CollectionStatus::Backlog;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['item:read', 'item:write'])]
    private int $playtimeMinutes = 0;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 10)]
    #[Groups(['item:read', 'item:write'])]
    private ?int $rating = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 2000)]
    #[Groups(['item:read', 'item:write'])]
    private ?string $notes = null;

    #[ORM\Column]
    #[Groups(['item:read'])]
    private \DateTimeImmutable $addedAt;

    #[ORM\Column(nullable: true)]
    #[Groups(['item:read', 'item:write'])]
    private ?\DateTimeImmutable $finishedAt = null;

    /** Importé depuis Steam : le temps de jeu et les succès viennent de la synchronisation */
    #[ORM\Column(options: ['default' => false])]
    #[Groups(['item:read'])]
    private bool $fromSteam = false;

    #[ORM\Column(options: ['default' => 0])]
    #[Groups(['item:read'])]
    private int $achievementsUnlocked = 0;

    #[ORM\Column(options: ['default' => 0])]
    #[Groups(['item:read'])]
    private int $achievementsTotal = 0;

    /** Date de la platine (100 % des succès), gardée même si le jeu ajoute des succès ensuite */
    #[ORM\Column(nullable: true)]
    #[Groups(['item:read'])]
    private ?\DateTimeImmutable $platinumAt = null;

    public function __construct()
    {
        $this->addedAt = new \DateTimeImmutable();
    }

    /** La plateforme doit faire partie de celles où le jeu existe */
    #[Assert\Callback]
    public function validatePlatform(ExecutionContextInterface $context): void
    {
        if ($this->game && $this->platform && !$this->game->getPlatforms()->contains($this->platform)) {
            $context->buildViolation("Ce jeu n'existe pas sur cette plateforme.")->atPath('platform')->addViolation();
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getOwner(): ?User { return $this->owner; }
    public function setOwner(User $owner): static { $this->owner = $owner; return $this; }
    public function getGame(): ?Game { return $this->game; }
    public function setGame(?Game $game): static { $this->game = $game; return $this; }
    public function getPlatform(): ?Platform { return $this->platform; }
    public function setPlatform(?Platform $platform): static { $this->platform = $platform; return $this; }
    public function getStatus(): CollectionStatus { return $this->status; }
    public function setStatus(CollectionStatus $status): static { $this->status = $status; return $this; }
    public function getPlaytimeMinutes(): int { return $this->playtimeMinutes; }
    public function setPlaytimeMinutes(int $playtimeMinutes): static { $this->playtimeMinutes = $playtimeMinutes; return $this; }
    public function getRating(): ?int { return $this->rating; }
    public function setRating(?int $rating): static { $this->rating = $rating; return $this; }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }
    public function getAddedAt(): \DateTimeImmutable { return $this->addedAt; }
    public function setAddedAt(\DateTimeImmutable $addedAt): static { $this->addedAt = $addedAt; return $this; }
    public function getFinishedAt(): ?\DateTimeImmutable { return $this->finishedAt; }
    public function setFinishedAt(?\DateTimeImmutable $finishedAt): static { $this->finishedAt = $finishedAt; return $this; }
    public function isFromSteam(): bool { return $this->fromSteam; }
    public function setFromSteam(bool $fromSteam): static { $this->fromSteam = $fromSteam; return $this; }
    public function getAchievementsUnlocked(): int { return $this->achievementsUnlocked; }
    public function getAchievementsTotal(): int { return $this->achievementsTotal; }
    public function getPlatinumAt(): ?\DateTimeImmutable { return $this->platinumAt; }

    /** Pourcentage de succès obtenus, ou null pour un jeu sans succès */
    #[Groups(['item:read'])]
    public function getAchievementsPercent(): ?float
    {
        return $this->achievementsTotal ? round(100 * $this->achievementsUnlocked / $this->achievementsTotal, 1) : null;
    }

    /** Met à jour la progression ; la platine est datée du dernier succès obtenu, la première fois que tout est fait */
    public function setAchievementProgress(int $unlocked, int $total, ?\DateTimeImmutable $lastUnlock): static
    {
        $this->achievementsUnlocked = $unlocked;
        $this->achievementsTotal = $total;
        if ($total > 0 && $unlocked === $total && null === $this->platinumAt) {
            $this->platinumAt = $lastUnlock ?? new \DateTimeImmutable();
        }

        return $this;
    }
}
