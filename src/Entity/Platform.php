<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/** Une plateforme de jeu (PC, PlayStation 5, Switch…) : catalogue commun, lisible par tous */
#[ORM\Entity]
#[UniqueEntity('slug')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['platform:read']],
    denormalizationContext: ['groups' => ['platform:write']],
    order: ['name' => 'ASC'],
    paginationEnabled: false,
)]
class Platform
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups(['platform:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank, Assert\Length(max: 80)]
    #[Groups(['platform:read', 'platform:write', 'game:read', 'item:read'])]
    private string $name = '';

    #[ORM\Column(length: 80, unique: true)]
    #[Assert\NotBlank, Assert\Regex('/^[a-z0-9-]+$/')]
    #[Groups(['platform:read', 'platform:write', 'game:read', 'item:read'])]
    private string $slug = '';

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank, Assert\Length(max: 80)]
    #[Groups(['platform:read', 'platform:write'])]
    private string $maker = '';

    public function __construct(string $name = '', string $slug = '', string $maker = '')
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->maker = $maker;
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getMaker(): string { return $this->maker; }
    public function setMaker(string $maker): static { $this->maker = $maker; return $this; }
}
