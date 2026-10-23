<?php

namespace App\Reward;

use App\Entity\CollectionItem;
use App\Entity\PlayerAchievement;
use App\Entity\User;
use App\Entity\UserReward;
use Doctrine\ORM\EntityManagerInterface;

/** Calcule les mesures d'un joueur et enregistre les récompenses qu'il vient d'atteindre */
final readonly class RewardEngine
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /** @return array<string, int> la valeur de chaque mesure de Rewards */
    public function metrics(User $user): array
    {
        $items = $this->em->createQueryBuilder()
            ->select('COUNT(i.id) AS games', 'COALESCE(SUM(i.playtimeMinutes), 0) AS minutes', 'COALESCE(MAX(i.playtimeMinutes), 0) AS best', 'COUNT(i.platinumAt) AS platinums')
            ->from(CollectionItem::class, 'i')
            ->where('i.owner = :user')->setParameter('user', $user)
            ->getQuery()->getSingleResult();

        $achievements = $this->em->createQueryBuilder()
            ->select('COUNT(p.id) AS total', 'SUM(CASE WHEN a.globalPercent < 1 THEN 1 ELSE 0 END) AS rare')
            ->from(PlayerAchievement::class, 'p')
            ->join('p.achievement', 'a')
            ->where('p.owner = :user')->setParameter('user', $user)
            ->getQuery()->getSingleResult();

        return [
            Rewards::GAMES => (int) $items['games'],
            Rewards::TOTAL_HOURS => intdiv((int) $items['minutes'], 60),
            Rewards::BEST_HOURS => intdiv((int) $items['best'], 60),
            Rewards::PLATINUMS => (int) $items['platinums'],
            Rewards::ACHIEVEMENTS => (int) $achievements['total'],
            Rewards::RARE => (int) $achievements['rare'],
        ];
    }

    /**
     * Enregistre les récompenses atteintes et pas encore débloquées.
     *
     * @return array<string, UserReward> toutes les récompenses débloquées du joueur, par code
     */
    public function evaluate(User $user): array
    {
        $unlocked = [];
        foreach ($this->em->getRepository(UserReward::class)->findBy(['owner' => $user]) as $reward) {
            $unlocked[$reward->getCode()] = $reward;
        }

        $metrics = $this->metrics($user);
        foreach (Rewards::ALL as $reward) {
            if (!isset($unlocked[$reward['code']]) && $metrics[$reward['metric']] >= $reward['threshold']) {
                $this->em->persist($unlocked[$reward['code']] = new UserReward($user, $reward['code']));
            }
        }
        $this->em->flush();

        return $unlocked;
    }
}
