<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\ExactFilter;
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
            'order[:property]' => new QueryParameter(filter: new SortFilter(), properties: ['addedAt', 'playtimeMinutes', 'rating']),
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
}
