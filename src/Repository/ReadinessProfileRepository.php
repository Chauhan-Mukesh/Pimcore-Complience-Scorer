<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Repository;

use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ReadinessProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReadinessProfile>
 */
final class ReadinessProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReadinessProfile::class);
    }

    /**
     * Returns all active ReadinessProfiles that target the given Pimcore class name.
     *
     * @return ReadinessProfile[]
     */
    public function findActiveByClassName(string $className): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.pimcoreClassName = :className')
            ->andWhere('p.isActive = :active')
            ->setParameter('className', $className)
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns all active profiles regardless of class.
     *
     * @return ReadinessProfile[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
