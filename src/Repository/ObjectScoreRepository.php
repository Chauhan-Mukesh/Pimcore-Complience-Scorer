<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Repository;

use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ObjectScore;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ObjectScore>
 */
final class ObjectScoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ObjectScore::class);
    }

    /**
     * Returns all scores for the given Pimcore object, one per active profile.
     *
     * @return ObjectScore[]
     */
    public function findByObjectId(int $objectId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.objectId = :objectId')
            ->setParameter('objectId', $objectId)
            ->orderBy('s.calculatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns the score for a specific object + profile combination, or null if not yet calculated.
     */
    public function findByObjectAndProfile(int $objectId, string $profileId): ?ObjectScore
    {
        return $this->createQueryBuilder('s')
            ->where('s.objectId = :objectId')
            ->andWhere('s.profileId = :profileId')
            ->setParameter('objectId', $objectId)
            ->setParameter('profileId', $profileId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Persists or updates an ObjectScore (upsert semantics).
     *
     * Finds an existing record for the same (objectId, profileId) pair and updates it,
     * or inserts a new one if none exists.
     *
     * @throws ORMException
     */
    public function upsert(ObjectScore $newScore): void
    {
        $em = $this->getEntityManager();

        $existing = $this->findByObjectAndProfile($newScore->getObjectId(), $newScore->getProfileId());

        if ($existing instanceof ObjectScore) {
            $existing->setScore($newScore->getScore());
            $existing->setMissingFieldsJson($newScore->getMissingFieldsJson());
            $existing->setCalculatedAt($newScore->getCalculatedAt());
        } else {
            $em->persist($newScore);
        }

        $em->flush();
    }
}
