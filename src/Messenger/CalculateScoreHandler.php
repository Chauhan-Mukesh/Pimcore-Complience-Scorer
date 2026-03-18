<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Messenger;

use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Repository\ObjectScoreRepository;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Repository\ReadinessProfileRepository;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Service\ReadinessScoreCalculator;
use Pimcore\Model\DataObject\Concrete;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles CalculateScoreMessage by computing and persisting readiness scores
 * for all active profiles that apply to the updated DataObject.
 *
 * This runs in an async Messenger worker process — never on the HTTP request thread.
 */
#[AsMessageHandler]
final class CalculateScoreHandler
{
    public function __construct(
        private readonly ReadinessProfileRepository $profileRepository,
        private readonly ReadinessScoreCalculator $calculator,
        private readonly ObjectScoreRepository $scoreRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CalculateScoreMessage $message): void
    {
        $objectId = $message->objectId;
        $object = Concrete::getById($objectId);

        if (!$object instanceof Concrete) {
            $this->logger->warning(
                'ReadinessShield: DataObject {objectId} not found — skipping score calculation.',
                ['objectId' => $objectId],
            );

            return;
        }

        $className = $object->getClassName();
        $profiles = $this->profileRepository->findActiveByClassName($className);

        if (count($profiles) === 0) {
            $this->logger->debug(
                'ReadinessShield: No active profiles for class {className} (objectId={objectId}).',
                ['className' => $className, 'objectId' => $objectId],
            );

            return;
        }

        foreach ($profiles as $profile) {
            try {
                $score = $this->calculator->calculate($object, $profile);
                $this->scoreRepository->upsert($score);

                $this->logger->info(
                    'ReadinessShield: Score calculated for objectId={objectId}, profile={profileName}: {score}%',
                    [
                        'objectId'    => $objectId,
                        'profileName' => $profile->getName(),
                        'score'       => $score->getScore(),
                    ],
                );
            } catch (\Throwable $e) {
                $this->logger->error(
                    'ReadinessShield: Error calculating score for objectId={objectId}, profile={profileName}: {error}',
                    [
                        'objectId'    => $objectId,
                        'profileName' => $profile->getName(),
                        'error'       => $e->getMessage(),
                        'exception'   => $e,
                    ],
                );
            }
        }
    }
}
