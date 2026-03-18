<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Controller\Api;

use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Messenger\CalculateScoreMessage;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Repository\ObjectScoreRepository;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Repository\ReadinessProfileRepository;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * REST API endpoints consumed by the Pimcore Studio sidebar widget.
 *
 * All endpoints require ROLE_PIMCORE_USER.
 * State-changing endpoints are protected by Symfony CSRF (handled at the bundle level via
 * Pimcore's built-in CSRF listener; stateless JWT is supported by passing X-pimcore-csrf header).
 */
#[Route('/api/readiness', name: 'pimcore_market_readiness_shield_api_')]
#[IsGranted('ROLE_PIMCORE_USER')]
final class ScoreController extends AbstractController
{
    public function __construct(
        private readonly ObjectScoreRepository $scoreRepository,
        private readonly ReadinessProfileRepository $profileRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    /**
     * Returns the current readiness scores for a DataObject across all applicable profiles.
     *
     * If no scores exist yet, dispatches a calculation job and returns an empty pending response.
     *
     * @return JsonResponse Shape: { objectId: int, profiles: ProfileScore[] }
     *
     * ProfileScore shape:
     *   { profileId: string, profileName: string, score: float, missingFields: MissingField[], calculatedAt: string }
     *
     * MissingField shape:
     *   { fieldPath: string, label: string, weight: float, tabHint: string|null }
     */
    #[Route('/score/{objectId}', name: 'score', methods: ['GET'], requirements: ['objectId' => '\d+'])]
    public function score(int $objectId): JsonResponse
    {
        $object = Concrete::getById($objectId);

        if (!$object instanceof Concrete) {
            return $this->json(['error' => 'Object not found.'], Response::HTTP_NOT_FOUND);
        }

        $scores = $this->scoreRepository->findByObjectId($objectId);

        // If no scores exist, dispatch calculation and return pending state.
        if (count($scores) === 0) {
            $this->messageBus->dispatch(new CalculateScoreMessage($objectId));

            return $this->json([
                'objectId' => $objectId,
                'status'   => 'pending',
                'profiles' => [],
            ], Response::HTTP_ACCEPTED);
        }

        $profileIds = array_map(static fn ($s) => $s->getProfileId(), $scores);
        $profilesById = [];

        foreach ($this->profileRepository->findBy(['id' => $profileIds]) as $profile) {
            $profilesById[$profile->getId()] = $profile->getName();
        }

        $profileScores = array_map(
            static fn ($score) => [
                'profileId'       => $score->getProfileId(),
                'profileName'     => $profilesById[$score->getProfileId()] ?? 'Unknown Profile',
                'score'           => $score->getScore(),
                'missingFields'   => $score->getMissingFieldsJson(),
                'dimensionScores' => $score->getDimensionScores(),
                'severityCounts'  => $score->getSeverityCounts(),
                'calculatedAt'    => $score->getCalculatedAt()->format(\DateTimeInterface::ATOM),
            ],
            $scores,
        );

        return $this->json([
            'objectId' => $objectId,
            'status'   => 'ready',
            'profiles' => $profileScores,
        ]);
    }

    /**
     * Triggers an asynchronous recalculation of scores for the given object.
     *
     * Returns 202 Accepted immediately; the worker processes the message in the background.
     */
    #[Route('/score/{objectId}/recalculate', name: 'recalculate', methods: ['POST'], requirements: ['objectId' => '\d+'])]
    #[IsGranted('ROLE_PIMCORE_USER')]
    public function recalculate(int $objectId): JsonResponse
    {
        $object = Concrete::getById($objectId);

        if (!$object instanceof Concrete) {
            return $this->json(['error' => 'Object not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->messageBus->dispatch(new CalculateScoreMessage($objectId));

        return $this->json([
            'message'  => 'Score recalculation queued.',
            'objectId' => $objectId,
        ], Response::HTTP_ACCEPTED);
    }
}
