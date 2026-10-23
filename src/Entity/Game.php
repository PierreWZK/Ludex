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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/** Un jeu du catalogue commun : lisible par tous, modifiable par l'administrateur */
#[ORM\Entity]
#[ApiResource(
    operations: [
        new GetCollection(parameters: [
            'title' => new QueryParameter(filter: new PartialSearchFilter(), property: 'title', description: 'Une partie du titre'),
            'platform' => new QueryParameter(filter: new ExactFilter(), property: 'platforms.slug', description: 'Slug de plateforme (ps5, switch…)'),
            'genre' => new QueryParameter(filter: new ExactFilter(), property: 'genres.slug', description: 'Slug de genre (rpg, aventure…)'),
            'order[:property]' => new QueryParameter(filter: new SortFilter(), properties: ['title', 'releaseYear']),
        ]),
        new Get(),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['game:read']],
    denormalizationContext: ['groups' => ['game:write']],
    order: ['title' => 'ASC'],
)]
class Game
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups(['game:read', 'item:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 160)]
    #[Assert\NotBlank, Assert\Length(max: 160)]
    #[Groups(['game:read', 'game:write', 'item:read'])]
    private string $title = '';

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1970, max: 2100)]
    #[Groups(['game:read', 'game:write', 'item:read'])]
    private ?int $releaseYear = null;

    /** @var Collection<int, Platform> */
    #[ORM\ManyToMany(targetEntity: Platform::class)]
    #[Assert\Count(min: 1)]
    #[Groups(['game:read', 'game:write'])]
    private Collection $platforms;

    /** @var Collection<int, Genre> */
    #[ORM\ManyToMany(targetEntity: Genre::class)]
    #[Groups(['game:read', 'game:write', 'item:read'])]
    private Collection $genres;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(requireTld: true)]
    #[Groups(['game:read', 'game:write', 'item:read'])]
    private ?string $coverUrl = null;

    public function __construct(string $title = '', ?int $releaseYear = null)
    {
        $this->title = $title;
        $this->releaseYear = $releaseYear;
        $this->platforms = new ArrayCollection();
        $this->genres = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getReleaseYear(): ?int { return $this->releaseYear; }
    public function setReleaseYear(?int $releaseYear): static { $this->releaseYear = $releaseYear; return $this; }
    public function getCoverUrl(): ?string { return $this->coverUrl; }
    public function setCoverUrl(?string $coverUrl): static { $this->coverUrl = $coverUrl; return $this; }

    /** @return Collection<int, Platform> */
    public function getPlatforms(): Collection { return $this->platforms; }

    public function addPlatform(Platform $platform): static
    {
        if (!$this->platforms->contains($platform)) {
            $this->platforms->add($platform);
        }

        return $this;
    }

    public function removePlatform(Platform $platform): static { $this->platforms->removeElement($platform); return $this; }

    /** @return Collection<int, Genre> */
    public function getGenres(): Collection { return $this->genres; }

    public function addGenre(Genre $genre): static
    {
        if (!$this->genres->contains($genre)) {
            $this->genres->add($genre);
        }

        return $this;
    }

    public function removeGenre(Genre $genre): static { $this->genres->removeElement($genre); return $this; }
}
