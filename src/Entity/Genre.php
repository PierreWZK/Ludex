<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/** Un genre (aventure, RPG, course…) : sert à filtrer le catalogue et la collection */
#[ORM\Entity]
#[ApiResource(
    operations: [new GetCollection(), new Get()],
    normalizationContext: ['groups' => ['genre:read']],
    order: ['name' => 'ASC'],
    paginationEnabled: false,
)]
class Genre
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups(['genre:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 60)]
    #[Groups(['genre:read', 'game:read', 'item:read'])]
    private string $name;

    #[ORM\Column(length: 60, unique: true)]
    #[Groups(['genre:read', 'game:read', 'item:read'])]
    private string $slug;

    public function __construct(string $name, string $slug)
    {
        $this->name = $name;
        $this->slug = $slug;
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getSlug(): string { return $this->slug; }
}
