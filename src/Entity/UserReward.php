<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/** Une récompense débloquée par un joueur (voir App\Reward\Rewards pour les définitions) */
#[ORM\Entity]
#[ORM\UniqueConstraint(fields: ['owner', 'code'])]
class UserReward
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $owner;

    #[ORM\Column(length: 40)]
    private string $code;

    #[ORM\Column]
    private \DateTimeImmutable $unlockedAt;

    public function __construct(User $owner, string $code)
    {
        $this->owner = $owner;
        $this->code = $code;
        $this->unlockedAt = new \DateTimeImmutable();
    }

    public function getCode(): string { return $this->code; }
    public function getUnlockedAt(): \DateTimeImmutable { return $this->unlockedAt; }
}
