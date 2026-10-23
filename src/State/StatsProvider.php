<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Stats;
use App\Entity\CollectionItem;
use App\Entity\WishlistItem;
use App\Enum\CollectionStatus;
use App\Reward\RewardEngine;
use App\Reward\Rewards;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Les statistiques en quelques requêtes groupées, quelle que soit la taille de la collection.
 *
 * @implements ProviderInterface<Stats>
 */
final readonly class StatsProvider implements ProviderInterface
{
    public function __construct(private EntityManagerInterface $em, private Security $security, private RewardEngine $rewards)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Stats
    {
        $owner = $this->security->getUser();

        $totals = $this->em->createQueryBuilder()
            ->select('COUNT(i.id) AS games', 'COALESCE(SUM(i.playtimeMinutes), 0) AS minutes', 'AVG(i.rating) AS rating')
            ->from(CollectionItem::class, 'i')
            ->where('i.owner = :owner')->setParameter('owner', $owner)
            ->getQuery()->getSingleResult();

        $byStatus = array_fill_keys(array_map(fn (CollectionStatus $s) => $s->value, CollectionStatus::cases()), 0);
        $rows = $this->em->createQueryBuilder()
            ->select('i.status AS status', 'COUNT(i.id) AS games')
            ->from(CollectionItem::class, 'i')
            ->where('i.owner = :owner')->setParameter('owner', $owner)
            ->groupBy('i.status')
            ->getQuery()->getArrayResult();
        foreach ($rows as $row) {
            $status = $row['status'] instanceof CollectionStatus ? $row['status']->value : $row['status'];
            $byStatus[$status] = (int) $row['games'];
        }

        $byPlatform = array_map(fn (array $row) => [
            'platform' => $row['platform'],
            'slug' => $row['slug'],
            'games' => (int) $row['games'],
            'playtimeMinutes' => (int) $row['minutes'],
        ], $this->em->createQueryBuilder()
            ->select('p.name AS platform', 'p.slug AS slug', 'COUNT(i.id) AS games', 'COALESCE(SUM(i.playtimeMinutes), 0) AS minutes')
            ->from(CollectionItem::class, 'i')
            ->join('i.platform', 'p')
            ->where('i.owner = :owner')->setParameter('owner', $owner)
            ->groupBy('p.id, p.name, p.slug')
            ->orderBy('games', 'DESC')
            ->getQuery()->getArrayResult());

        $wishlist = (int) $this->em->createQueryBuilder()
            ->select('COUNT(w.id)')
            ->from(WishlistItem::class, 'w')
            ->where('w.owner = :owner')->setParameter('owner', $owner)
            ->getQuery()->getSingleScalarResult();

        $minutes = (int) $totals['minutes'];
        $metrics = $this->rewards->metrics($owner);

        return new Stats(
            games: (int) $totals['games'],
            playtimeMinutes: $minutes,
            playtimeHours: round($minutes / 60, 1),
            averageRating: null === $totals['rating'] ? null : round((float) $totals['rating'], 1),
            wishlist: $wishlist,
            achievements: $metrics[Rewards::ACHIEVEMENTS],
            platinums: $metrics[Rewards::PLATINUMS],
            byStatus: $byStatus,
            byPlatform: $byPlatform,
        );
    }
}
