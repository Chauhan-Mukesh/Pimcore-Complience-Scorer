<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Repository;

use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ReadinessRule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReadinessRule>
 */
final class ReadinessRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReadinessRule::class);
    }

    /**
     * Returns all rules for a given profile ID, ordered by sortOrder.
     *
     * @return ReadinessRule[]
     */
    public function findByProfileId(string $profileId): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.profile', 'p')
            ->where('p.id = :profileId')
            ->setParameter('profileId', $profileId)
            ->orderBy('r.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
