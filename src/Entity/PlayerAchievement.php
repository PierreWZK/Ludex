<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/** Un succès obtenu par un joueur, avec sa date */
#[ORM\Entity]
#[ORM\UniqueConstraint(fields: ['owner', 'achievement'])]
class PlayerAchievement
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $owner;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Achievement $achievement;

    #[ORM\Column]
    private \DateTimeImmutable $unlockedAt;

    public function __construct(User $owner, Achievement $achievement, \DateTimeImmutable $unlockedAt)
    {
        $this->owner = $owner;
        $this->achievement = $achievement;
        $this->unlockedAt = $unlockedAt;
    }

    public function getOwner(): User { return $this->owner; }
    public function getAchievement(): Achievement { return $this->achievement; }
    public function getUnlockedAt(): \DateTimeImmutable { return $this->unlockedAt; }
}
