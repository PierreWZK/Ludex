<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\AchievementProgress;
use App\Entity\Achievement;
use App\Entity\CollectionItem;
use App\Entity\PlayerAchievement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<AchievementProgress> */
final readonly class AchievementProgressProvider implements ProviderInterface
{
    public function __construct(private EntityManagerInterface $em, private Security $security, private RequestStack $requests)
    {
    }

    /** @return list<AchievementProgress> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $item = $this->em->find(CollectionItem::class, (int) $uriVariables['id']);
        // Le jeu d'un autre joueur n'existe pas, comme pour la collection
        if (!$item || $item->getOwner() !== $this->security->getUser()) {
            throw new NotFoundHttpException('Not Found');
        }

        $unlocked = [];
        foreach ($this->em->createQueryBuilder()
            ->select('p')->from(PlayerAchievement::class, 'p')->join('p.achievement', 'a')
            ->where('p.owner = :user')->andWhere('a.game = :game')
            ->setParameter('user', $item->getOwner())->setParameter('game', $item->getGame())
            ->getQuery()->getResult() as $p) {
            $unlocked[$p->getAchievement()->getId()] = $p->getUnlockedAt();
        }

        $rows = array_map(fn (Achievement $a) => new AchievementProgress(
            apiName: $a->getApiName(),
            name: $a->getName(),
            description: $a->getDescription(),
            icon: $a->getIcon(),
            hidden: $a->isHidden(),
            rarity: $a->getGlobalPercent(),
            unlocked: isset($unlocked[$a->getId()]),
            unlockedAt: $unlocked[$a->getId()] ?? null,
        ), $this->em->getRepository(Achievement::class)->findBy(['game' => $item->getGame()]));

        $state = $this->requests->getCurrentRequest()?->query->get('state');
        if (\in_array($state, ['todo', 'done'], true)) {
            $rows = array_values(array_filter($rows, fn (AchievementProgress $r) => $r->unlocked === ('done' === $state)));
        }

        // À faire : les plus courants d'abord, les plus faciles à aller chercher ; obtenus : les plus récents d'abord
        usort($rows, fn (AchievementProgress $a, AchievementProgress $b) => [$a->unlocked, $b->unlockedAt, $b->rarity] <=> [$b->unlocked, $a->unlockedAt, $a->rarity]);

        return $rows;
    }
}
