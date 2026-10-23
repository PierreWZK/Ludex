<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\ExactFilter;
use ApiPlatform\Doctrine\Orm\Filter\SortFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use App\State\OwnerProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/** Un jeu de la liste d'envies d'un joueur */
#[ORM\Entity]
#[ORM\UniqueConstraint(fields: ['owner', 'game'])]
#[UniqueEntity(fields: ['owner', 'game'], message: "Ce jeu est déjà dans votre liste d'envies.", errorPath: 'game')]
#[ApiResource(
    operations: [
        new GetCollection(parameters: [
            'priority' => new QueryParameter(filter: new ExactFilter(), property: 'priority', description: '1 (très envie) à 3'),
            'order[:property]' => new QueryParameter(filter: new SortFilter(), properties: ['addedAt', 'priority']),
        ]),
        new Get(security: "is_granted('OWNER', object)"),
        new Post(processor: OwnerProcessor::class),
        new Patch(security: "is_granted('OWNER', object)", processor: OwnerProcessor::class),
        new Delete(security: "is_granted('OWNER', object)"),
    ],
    normalizationContext: ['groups' => ['item:read']],
    denormalizationContext: ['groups' => ['item:write']],
    order: ['priority' => 'ASC', 'addedAt' => 'DESC'],
    security: "is_granted('ROLE_USER')",
)]
class WishlistItem implements OwnedInterface
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

    #[ORM\Column(type: 'smallint')]
    #[Assert\Range(min: 1, max: 3)]
    #[Groups(['item:read', 'item:write'])]
    private int $priority = 2;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Groups(['item:read', 'item:write'])]
    private ?string $note = null;

    #[ORM\Column]
    #[Groups(['item:read'])]
    private \DateTimeImmutable $addedAt;

    public function __construct()
    {
        $this->addedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getOwner(): ?User { return $this->owner; }
    public function setOwner(User $owner): static { $this->owner = $owner; return $this; }
    public function getGame(): ?Game { return $this->game; }
    public function setGame(?Game $game): static { $this->game = $game; return $this; }
    public function getPriority(): int { return $this->priority; }
    public function setPriority(int $priority): static { $this->priority = $priority; return $this; }
    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $note): static { $this->note = $note; return $this; }
    public function getAddedAt(): \DateTimeImmutable { return $this->addedAt; }
}
