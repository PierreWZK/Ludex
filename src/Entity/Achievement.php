<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/** Un succès d'un jeu Steam (le schéma du jeu), commun à tous les joueurs */
#[ORM\Entity]
#[ORM\UniqueConstraint(fields: ['game', 'apiName'])]
class Achievement
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Game $game;

    /** Le nom technique du succès chez Steam, stable d'une langue à l'autre */
    #[ORM\Column(length: 128)]
    private string $apiName;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $hidden = false;

    /** Part des joueurs Steam qui l'ont obtenu, en % */
    #[ORM\Column(nullable: true)]
    private ?float $globalPercent = null;

    public function __construct(Game $game, string $apiName)
    {
        $this->game = $game;
        $this->apiName = $apiName;
    }

    public function getId(): ?int { return $this->id; }
    public function getGame(): Game { return $this->game; }
    public function getApiName(): string { return $this->apiName; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getIcon(): ?string { return $this->icon; }
    public function isHidden(): bool { return $this->hidden; }
    public function getGlobalPercent(): ?float { return $this->globalPercent; }
    public function setGlobalPercent(?float $globalPercent): static { $this->globalPercent = $globalPercent; return $this; }

    public function describe(string $name, ?string $description, ?string $icon, bool $hidden): static
    {
        $this->name = mb_substr($name, 0, 255);
        $this->description = $description;
        $this->icon = $icon;
        $this->hidden = $hidden;

        return $this;
    }
}
